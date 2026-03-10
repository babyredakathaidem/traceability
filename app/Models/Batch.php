<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Support\Collection;

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
        'certifications'  => 'array',
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

    public function originEnterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'origin_enterprise_id');
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

    public function certificates()
    {
        return $this->belongsToMany(Certificate::class, 'batch_certificates')
            ->withPivot('applied_at');
    }

    public function pendingTransfer(): HasOne
    {
        return $this->hasOne(BatchTransfer::class)->where('status', 'pending')->latest();
    }

    // ── Helpers ───────────────────────────────────────────

    public function getDisplayName(): string
    {
        return $this->product?->name ?? $this->product_name ?? 'Không tên';
    }

    public function isRecalled(): bool
    {
        return $this->status === 'recalled';
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAS CADE RECALL — Thu hồi dây chuyền (TCVN 12850:2019)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Lấy toàn bộ "hậu duệ" của lô hàng này (đệ quy, giới hạn 10 tầng).
     *
     * Cơ chế duyệt:
     *   1. childBatches: tìm lô con được tách ra (batch_type='split', parent_batch_id=this->id)
     *   2. batch_lineage: tìm lô đầu ra khi lô này là đầu vào của một phép merge
     *      (transformation_type='merge', input_batch_id=this->id)
     *
     * Trả về Collection<Batch> — không trùng lặp, không bao gồm lô gốc.
     *
     * @param int $maxDepth  Giới hạn độ sâu đệ quy (tránh vòng lặp vô hạn)
     * @param array $visited  Danh sách batch_id đã duyệt (internal, không truyền từ ngoài)
     * @return \Illuminate\Support\Collection<Batch>
     */
    public function getAllDescendants(int $maxDepth = 10, array &$visited = []): Collection
    {
        // Đánh dấu lô hiện tại đã duyệt — tránh vòng lặp
        $visited[] = $this->id;

        if ($maxDepth <= 0) {
            return collect();
        }

        $descendants = collect();

        // ── 1. Lô con qua split (parent_batch_id) ─────────────────────
        $splitChildren = Batch::where('parent_batch_id', $this->id)
            ->whereNotIn('id', $visited)
            ->with(['enterprise:id,name,code'])
            ->get();

        foreach ($splitChildren as $child) {
            $descendants->push($child);
            // Đệ quy xuống lô con của lô con
            $grandChildren = $child->getAllDescendants($maxDepth - 1, $visited);
            $descendants = $descendants->merge($grandChildren);
        }

        // ── 2. Lô đầu ra qua merge (batch_lineage) ────────────────────
        $mergeOutputIds = BatchLineage::where('input_batch_id', $this->id)
            ->where('transformation_type', 'merge')
            ->pluck('output_batch_id');

        $mergeOutputs = Batch::whereIn('id', $mergeOutputIds)
            ->whereNotIn('id', $visited)
            ->with(['enterprise:id,name,code'])
            ->get();

        foreach ($mergeOutputs as $output) {
            $descendants->push($output);
            // Đệ quy tiếp xuống các lô con của lô gộp
            $grandChildren = $output->getAllDescendants($maxDepth - 1, $visited);
            $descendants = $descendants->merge($grandChildren);
        }

        // Loại trùng theo id trước khi trả về
        return $descendants->unique('id')->values();
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
                    'batch'     => $parent,
                    'relation'  => 'split_from',
                    'ancestors' => $parent->buildAncestors(),
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
                        'batch'     => $inputBatch,
                        'relation'  => 'merged_from',
                        'quantity'  => $lineage->quantity,
                        'unit'      => $lineage->unit,
                        'ancestors' => $inputBatch->buildAncestors(),
                    ];
                }
            }
        }

        return $ancestors;
    }
}