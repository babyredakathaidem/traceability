<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraceEvent extends Model
{
    protected $fillable = [
        'enterprise_id',
        'batch_id',
        'event_type',   // legacy — giữ lại backward compat
        'cte_code',     // mã CTE chuẩn hoặc 'custom'
        'event_time',
        'location',     // legacy
        'note',         // legacy
        'kde_data',     // JSON 5W đầy đủ
        'who_name',
        'where_address',
        'where_lat',
        'where_lng',
        'why_reason',
        'attachments',  // [{cid, url, name, mime_type}]
        'status',
        'content_hash',
        'ipfs_cid',
        'ipfs_url',
        'tx_hash',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'event_time'   => 'datetime',
        'published_at' => 'datetime',
        'kde_data'     => 'array',
        'attachments'  => 'array',
        'where_lat'    => 'float',
        'where_lng'    => 'float',
    ];

    // ── Relations ─────────────────────────────────────────

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // ── Helpers ───────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function hasGps(): bool
    {
        return !is_null($this->where_lat) && !is_null($this->where_lng);
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Tên hiển thị của CTE — lấy từ template hoặc event_type cũ
     */
    public function getDisplayName(): string
    {
        if ($this->cte_code && $this->cte_code !== 'custom') {
            // Có thể eager load nếu cần
            $tpl = CteTemplate::where('code', $this->cte_code)->first();
            if ($tpl) return $tpl->name_vi;
        }
        return $this->event_type ?? 'Sự kiện tùy chỉnh';
    }

    /**
     * Payload chuẩn upload IPFS — bất biến sau publish
     * Đầy đủ 5W theo TCVN 12850:2019
     */
    public function toIpfsPayload(): array
    {
        $batch = $this->batch;

        return [
            // Meta hệ thống
            'system'        => 'AGU Traceability',
            'version'       => '1.0',
            'tcvn_ref'      => 'TCVN 12850:2019',
            'timestamp'     => now()->toISOString(),

            // Định danh đối tượng truy xuất
            'event_id'      => $this->id,
            'batch_id'      => $this->batch_id,
            'batch_code'    => $batch?->code,
            'gtin'          => $batch?->product?->gtin,
            'product_name'  => $batch?->getDisplayName(),
            'enterprise'    => $batch?->enterprise?->name,
            'enterprise_code' => $batch?->enterprise?->code,

            // CTE
            'cte_code'      => $this->cte_code ?? 'custom',
            'cte_name'      => $this->getDisplayName(),

            // WHEN
            'event_time'    => optional($this->event_time)->toISOString(),
            'recorded_at'   => $this->created_at?->toISOString(),

            // 5W — KDE đầy đủ
            'kde'           => $this->kde_data ?? [],

            // Index nhanh từ KDE
            'who'           => $this->who_name,
            'where'         => $this->where_address,
            'gps'           => $this->hasGps()
                ? ['lat' => $this->where_lat, 'lng' => $this->where_lng]
                : null,
            'why'           => $this->why_reason,
            'note'          => $this->note,

            // Đính kèm
            'attachments'   => $this->attachments ?? [],
        ];
    }
}