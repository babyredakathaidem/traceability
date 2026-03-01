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
        'status',       // active | completed | recalled
        'completed_at',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date'     => 'date',
        'completed_at'    => 'datetime',
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
}