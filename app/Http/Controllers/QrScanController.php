<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\Qrcode;
use App\Models\QrScanLog;
use App\Models\TraceEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QrScanController extends Controller
{
    public function gatePublic(string $token)
    {
        return Inertia::render('Trace/GatePublic', ['token' => $token]);
    }

    public function gatePrivate(string $token)
    {
        return Inertia::render('Trace/GatePrivate', ['token' => $token]);
    }

    public function resolvePublic(Request $request, string $token)
    {
        $qr = Qrcode::with([
            'batch:id,code,product_name,product_id,enterprise_id,production_date,expiry_date,quantity,unit,status,batch_type,parent_batch_id',
            'batch.product:id,name,gtin,description,unit,image_path,category_id',
            'batch.product.category:id,name_vi,icon,code',
            'batch.enterprise:id,name,code,address_detail,province,phone,email',
        ])
            ->where('type', 'public')
            ->where('token', $token)
            ->first();

        if (!$qr) {
            $this->logInvalid($request, 'public', $token, 'token_not_found');
            $this->alertInvalid($token, 'public', 'invalid_token', 'Public token not found');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR không hợp lệ',
                'message' => 'QR công khai không tồn tại hoặc đã bị thu hồi.',
            ]);
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if ($lat === null || $lng === null) {
            $this->logScan($request, $qr, null, null, null, 'denied_no_location', 'no_location');
            $this->alert($qr, 'denied_no_location', "Public QR quét nhưng không có GPS. place={$qr->place_name}");
            return Inertia::render('Trace/Blocked', [
                'title'   => 'Cần bật định vị',
                'message' => 'QR công khai chỉ hợp lệ khi bật định vị để xác thực điểm phát hành.',
            ]);
        }

        if ($qr->allowed_lat === null || $qr->allowed_lng === null || $qr->allowed_radius_m === null) {
            $this->logScan($request, $qr, $lat, $lng, null, 'blocked', 'public_not_configured');
            $this->alert($qr, 'public_not_configured', "Public QR chưa cấu hình tọa độ/radius.");
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR chưa cấu hình',
                'message' => 'QR công khai chưa được cấu hình điểm phát hành.',
            ]);
        }

        $distance = (int) round($this->distanceMeters(
            (float) $lat, (float) $lng,
            (float) $qr->allowed_lat, (float) $qr->allowed_lng
        ));

        $ok = $distance <= (int) $qr->allowed_radius_m;

        if (!$ok) {
            $this->logScan($request, $qr, $lat, $lng, $distance, 'blocked', 'geo_mismatch');
            $this->alert($qr, 'geo_mismatch', "Sai vị trí. expected={$qr->place_name}, distance={$distance}m, radius={$qr->allowed_radius_m}m");
            return Inertia::render('Trace/Blocked', [
                'title'      => 'Sai vị trí quét',
                'message'    => "QR công khai chỉ hợp lệ trong phạm vi {$qr->allowed_radius_m}m quanh '{$qr->place_name}'. Hệ thống đã ghi nhận cảnh báo.",
                'distance_m' => $distance,
                'radius_m'   => (int) $qr->allowed_radius_m,
            ]);
        }

        $this->logScan($request, $qr, $lat, $lng, $distance, 'allowed', null);

        return Inertia::render('Trace/Show', [
            'mode'       => 'public',
            'batch'      => $this->formatBatch($qr->batch),
            'events'     => $this->loadEvents($qr->batch),
            'place_name' => $qr->place_name,
        ]);
    }

    public function resolvePrivate(Request $request, string $token)
    {
        $qr = Qrcode::with([
            'batch:id,code,product_name,product_id,enterprise_id,production_date,expiry_date,quantity,unit,status,batch_type,parent_batch_id',
            'batch.product:id,name,gtin,description,unit,image_path,category_id',
            'batch.product.category:id,name_vi,icon,code',
            'batch.enterprise:id,name,code,address_detail,province,phone,email',
        ])
            ->where('type', 'private')
            ->where('token', $token)
            ->first();

        if (!$qr) {
            $this->logInvalid($request, 'private', $token, 'token_not_found_or_deleted');
            $this->alertInvalid($token, 'private', 'invalid_token', 'Private token not found (expired/deleted)');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR không hợp lệ',
                'message' => 'QR riêng tư không tồn tại hoặc đã hết hiệu lực.',
            ]);
        }

        $now = now();

        if ($qr->expires_at && $now->greaterThan($qr->expires_at)) {
            $this->logScan($request, $qr, $request->input('lat'), $request->input('lng'), null, 'expired', 'private_expired');
            $this->alert($qr, 'private_expired', "Private QR hết hạn 48h. token={$token}");
            $qr->delete();
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR đã hết hiệu lực',
                'message' => 'QR riêng tư chỉ có hiệu lực 48 giờ kể từ lần quét đầu tiên.',
            ]);
        }

        if (!$qr->first_scanned_at) {
            $qr->first_scanned_at = $now;
            $qr->expires_at       = $now->copy()->addHours(48);
            $qr->save();
        }

        $this->logScan($request, $qr, $request->input('lat'), $request->input('lng'), null, 'allowed', null);

        return Inertia::render('Trace/Show', [
            'mode'       => 'private',
            'batch'      => $this->formatBatch($qr->batch),
            'events'     => $this->loadEvents($qr->batch),
            'expires_at' => optional($qr->expires_at)->toDateTimeString(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────

    private function formatBatch($batch): array
    {
        if (!$batch) return [];

        return [
            'id'              => $batch->id,
            'code'            => $batch->code,
            'product_name'    => $batch->product_name,
            'production_date' => optional($batch->production_date)->format('d/m/Y'),
            'expiry_date'     => optional($batch->expiry_date)->format('d/m/Y'),
            'quantity'        => $batch->quantity,
            'unit'            => $batch->unit,
            'status'          => $batch->status,
            'product'         => $batch->product ? [
                'name'        => $batch->product->name,
                'gtin'        => $batch->product->gtin,
                'description' => $batch->product->description,
                'unit'        => $batch->product->unit,
                'image_path'  => $batch->product->image_path,
                'category'    => $batch->product->category ? [
                    'name_vi' => $batch->product->category->name_vi,
                    'icon'    => $batch->product->category->icon,
                    'code'    => $batch->product->category->code,
                ] : null,
            ] : null,
            'enterprise'      => $batch->enterprise ? [
                'name'    => $batch->enterprise->name,
                'code'    => $batch->enterprise->code,
                'address' => $batch->enterprise->full_address ?? null,
                'phone'   => $batch->enterprise->phone,
                'email'   => $batch->enterprise->email,
            ] : null,
        ];
    }

    /**
     * Load toàn bộ published events theo lineage của lô hàng.
     *
     * Giải thích thiết kế transfer trong hệ thống này:
     *   - Khi DN A transfer lô cho DN B → CÙNG 1 batch record, chỉ đổi enterprise_id
     *   - Nghĩa là events của DN A và DN B đều có cùng batch_id → tự động gom được
     *
     * Chỉ cần đệ quy thêm khi:
     *   - split: lô con có parent_batch_id → cần gom events từ lô cha
     *   - merged: lô gộp có nhiều input_batch_id → cần gom từ tất cả input
     */
    private function loadEvents($batch): array
    {
        if (!$batch) return [];

        // Thu thập tất cả batch_id liên quan qua lineage
        $batchIds = [];
        $this->collectAncestorBatchIds($batch->id, $batchIds, 0);

        // Load tất cả published events — bỏ transformation events (split/merge)
        $events = TraceEvent::with('batch.enterprise:id,name,code')
            ->whereIn('batch_id', $batchIds)
            ->where('status', 'published')
            ->whereNotIn('cte_code', ['split', 'merge'])
            ->orderBy('event_time')
            ->orderBy('id')
            ->get([
                'id', 'batch_id', 'enterprise_id', 'cte_code', 'event_type', 'event_time',
                'kde_data', 'who_name', 'where_address', 'where_lat', 'where_lng',
                'why_reason', 'note', 'attachments',
                'content_hash', 'ipfs_cid', 'ipfs_url', 'tx_hash', 'published_at',
            ]);

        return $events->map(fn($e) => [
            'id'             => $e->id,
            'cte_code'       => $e->cte_code ?? $e->event_type,
            'event_time'     => optional($e->event_time)->format('H:i d/m/Y'),
            'event_time_iso' => optional($e->event_time)->toISOString(),
            'who_name'       => $e->who_name,
            'where_address'  => $e->where_address,
            'where_lat'      => $e->where_lat,
            'where_lng'      => $e->where_lng,
            'why_reason'     => $e->why_reason,
            'note'           => $e->note,
            'kde_data'       => $e->kde_data ?? [],
            'attachments'    => $e->attachments ?? [],
            'ipfs_cid'       => $e->ipfs_cid,
            'ipfs_url'       => $e->ipfs_url,
            'tx_hash'        => $e->tx_hash,
            'published_at'   => optional($e->published_at)->format('d/m/Y H:i'),
            'content_hash'   => $e->content_hash,
            // Tên DN thực hiện bước này — hiển thị trên timeline
            'enterprise'     => $e->batch?->enterprise ? [
                'name' => $e->batch->enterprise->name,
                'code' => $e->batch->enterprise->code,
            ] : null,
        ])->toArray();
    }

    /**
     * Đệ quy thu thập batch_id trong cây lineage.
     *
     * Lưu ý quan trọng về transfer:
     *   Khi transfer xảy ra → KHÔNG tạo batch mới, chỉ đổi enterprise_id.
     *   Vì vậy batch_type='received' KHÔNG cần đệ quy thêm —
     *   events của tất cả DN đều đã dùng cùng batch_id.
     *
     * Chỉ cần đệ quy cho:
     *   - split  → lên parent_batch_id
     *   - merged → lên tất cả input_batch_id trong batch_lineage
     */
    private function collectAncestorBatchIds(int $batchId, array &$ids, int $depth): void
    {
        // Giới hạn độ sâu 10 tầng — tránh vòng lặp vô hạn
        if ($depth > 10 || in_array($batchId, $ids)) return;

        $ids[] = $batchId;

        $batch = Batch::select('id', 'batch_type', 'parent_batch_id')->find($batchId);
        if (!$batch) return;

        // Lô gốc hoặc received → dừng (received dùng cùng batch_id với lô gốc)
        if (in_array($batch->batch_type, ['original', 'received'])) return;

        // Split → đệ quy lên lô cha
        if ($batch->batch_type === 'split' && $batch->parent_batch_id) {
            $this->collectAncestorBatchIds($batch->parent_batch_id, $ids, $depth + 1);
        }

        // Merged → đệ quy lên tất cả input batches
        if ($batch->batch_type === 'merged') {
            $inputIds = BatchLineage::where('output_batch_id', $batchId)
                ->where('transformation_type', 'merge')
                ->pluck('input_batch_id');

            foreach ($inputIds as $inputId) {
                $this->collectAncestorBatchIds($inputId, $ids, $depth + 1);
            }
        }
    }

    // ── Alert helpers ─────────────────────────────────────

    private function alert(Qrcode $qr, string $type, string $detail): void
    {
        Alert::create([
            'enterprise_id' => $qr->enterprise_id,
            'batch_id'      => $qr->batch_id,
            'qrcode_id'     => $qr->id,
            'type'          => $type,
            'detail'        => $detail,
        ]);
    }

    private function alertInvalid(string $token, string $qrType, string $type, string $detail): void
    {
        Alert::create([
            'enterprise_id' => null,
            'batch_id'      => null,
            'qrcode_id'     => null,
            'type'          => $type,
            'detail'        => "[{$qrType}] token={$token} — {$detail}",
        ]);
    }

    // ── Scan log ──────────────────────────────────────────

    private function logInvalid(Request $request, string $type, string $token, string $reason): void
    {
        QrScanLog::create([
            'qrcode_id'       => null,
            'enterprise_id'   => null,
            'batch_id'        => null,
            'qr_type'         => $type,
            'token'           => $token,
            'scanned_at'      => now(),
            'lat'             => $request->input('lat'),
            'lng'             => $request->input('lng'),
            'distance_m'      => null,
            'device_name'     => $request->input('device_name'),
            'device_platform' => $request->input('device_platform'),
            'ip'              => $request->ip(),
            'user_agent'      => substr((string) $request->userAgent(), 0, 1000),
            'decision'        => 'invalid',
            'reason'          => $reason,
        ]);
    }

    private function logScan(
        Request $request, Qrcode $qr,
        $lat, $lng, $distance,
        string $decision, ?string $reason
    ): void {
        QrScanLog::create([
            'qrcode_id'           => $qr->id,
            'enterprise_id'       => $qr->enterprise_id,
            'batch_id'            => $qr->batch_id,
            'qr_type'             => $qr->type,
            'token'               => $qr->token,
            'expected_place_name' => $qr->type === 'public' ? $qr->place_name : null,
            'expected_lat'        => $qr->type === 'public' ? $qr->allowed_lat : null,
            'expected_lng'        => $qr->type === 'public' ? $qr->allowed_lng : null,
            'expected_radius_m'   => $qr->type === 'public' ? $qr->allowed_radius_m : null,
            'lat'                 => $lat,
            'lng'                 => $lng,
            'distance_m'          => $distance,
            'device_name'         => $request->input('device_name'),
            'device_platform'     => $request->input('device_platform'),
            'ip'                  => $request->ip(),
            'user_agent'          => substr((string) $request->userAgent(), 0, 1000),
            'decision'            => $decision,
            'reason'              => $reason,
        ]);
    }

    // ── Haversine distance ────────────────────────────────

    private function distanceMeters(
        float $lat1, float $lng1,
        float $lat2, float $lng2
    ): float {
        $R    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}