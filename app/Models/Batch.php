<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToTenant;

class Batch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'enterprise_id',
        'product_id',
        'code',
        'product_name',
        'description',
        'production_date',
        'expiry_date',
        'quantity',
        'unit',
        'status',
        'batch_type',
        'current_quantity',
        'origin_enterprise_id',
        'parent_batch_id',       
        'completed_at',
        'certifications',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date'     => 'date',
        'completed_at'    => 'datetime',
        'certifications'   => 'array',
    ];

    // ── Relations ─────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TraceEvent::class);
    }

    public function publishedEvents(): HasMany
    {
        return $this->hasMany(TraceEvent::class)->where('status', 'published');
    }

    public function qrcodes(): HasMany
    {
        return $this->hasMany(Qrcode::class);
    }

    public function activeRecall(): HasOne
    {
        return $this->hasOne(BatchRecall::class)->where('status', 'active')->latest();
    }

    public function recalls(): HasMany
    {
        return $this->hasMany(BatchRecall::class)->latest();
    }

    // ── Helpers ───────────────────────────────────────────

    public function getDisplayName(): string
    {
        return $this->product?->name ?? $this->product_name ?? 'Không rõ';
    }

    public function isRecalled(): bool
    {
        return $this->status === 'recalled';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Tính completeness score theo TCVN 12850
     * = required CTEs đã có event published / tổng required CTEs
     */
    public function completenessScore(): array
    {
        $category = $this->product?->category;
        if (!$category) {
            return ['score' => 0, 'required_total' => 0, 'required_done' => 0, 'missing' => []];
        }

        $requiredCtes = CteTemplate::where('category_id', $category->id)
            ->where('is_required', true)
            ->orderBy('step_order')
            ->get(['code', 'name_vi', 'step_order']);

        if ($requiredCtes->isEmpty()) {
            return ['score' => 100, 'required_total' => 0, 'required_done' => 0, 'missing' => []];
        }

        $publishedCteCodes = $this->publishedEvents()
            ->whereNotNull('cte_code')
            ->pluck('cte_code')
            ->unique()
            ->toArray();

        $missing = $requiredCtes->filter(
            fn($cte) => !in_array($cte->code, $publishedCteCodes)
        )->values();

        $done  = $requiredCtes->count() - $missing->count();
        $score = (int) round(($done / $requiredCtes->count()) * 100);

        return [
            'score'          => $score,
            'required_total' => $requiredCtes->count(),
            'required_done'  => $done,
            'missing'        => $missing->map(fn($c) => [
                'code'       => $c->code,
                'name_vi'    => $c->name_vi,
                'step_order' => $c->step_order,
            ])->toArray(),
        ];
    }

    /**
     * Lô đủ điều kiện publish QR (completeness 100%)
     */
    public function canPublishQr(): bool
    {
        return $this->completenessScore()['score'] === 100;
    }
    public function originEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'origin_enterprise_id');
    }

    public function parentBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'parent_batch_id');
    }

    public function childBatches(): HasMany
    {
        return $this->hasMany(Batch::class, 'parent_batch_id');
    }

    public function lineageAsInput(): HasMany
    {
        return $this->hasMany(BatchLineage::class, 'input_batch_id');
    }

    public function lineageAsOutput(): HasMany
    {
        return $this->hasMany(BatchLineage::class, 'output_batch_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BatchTransfer::class);
    }

    public function pendingTransfer(): HasOne
    {
        return $this->hasOne(BatchTransfer::class)->where('status', 'pending')->latest();
    }

    // ── buildAncestors — đệ quy lấy toàn bộ cây phả hệ ──────────
    public function buildAncestors(): array
    {
        $ancestors = [];

        // 1. Nếu có parent_batch_id (split) → đệ quy lên cha
        if ($this->parent_batch_id) {
            $parent = Batch::with(['enterprise', 'originEnterprise', 'publishedEvents'])
                ->find($this->parent_batch_id);
            if ($parent) {
                $ancestors[] = [
                    'batch'      => $parent,
                    'relation'   => 'split_from',
                    'ancestors'  => $parent->buildAncestors(),
                ];
            }
        }

        // 2. Nếu là lô merged → tìm inputs trong batch_lineage
        if ($this->batch_type === 'merged') {
            $inputs = BatchLineage::where('output_batch_id', $this->id)
                ->where('transformation_type', 'merge')
                ->with(['inputBatch.enterprise', 'inputBatch.originEnterprise'])
                ->get();

            foreach ($inputs as $lineage) {
                $inputBatch = $lineage->inputBatch;
                if ($inputBatch) {
                    $ancestors[] = [
                        'batch'    => $inputBatch,
                        'relation' => 'merged_from',
                        'quantity' => $lineage->quantity,
                        'unit'     => $lineage->unit,
                        'ancestors'=> $inputBatch->buildAncestors(),
                    ];
                }
            }
        }

        // 3. Nếu là received → tìm transfer event
        if ($this->batch_type === 'received') {
            $transfer = BatchTransfer::where('batch_id', $this->id)
                ->where('status', 'accepted')
                ->with(['fromEnterprise', 'transferEvent'])
                ->latest('accepted_at')
                ->first();

            if ($transfer) {
                $ancestors[] = [
                    'batch'    => $this,
                    'relation' => 'received_from',
                    'transfer' => $transfer,
                    'ancestors'=> [],
                ];
            }
        }

        return $ancestors;
    }
}