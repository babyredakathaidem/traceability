<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Qrcode;
use App\Models\QrScanLog;
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
        $qr = Qrcode::with('batch:id,code,product_name,enterprise_id')
            ->where('type', 'public')
            ->where('token', $token)
            ->first();

        if (!$qr) {
            $this->logInvalid($request, 'public', $token, 'token_not_found');
            $this->alertInvalid($token, 'public', 'invalid_token', 'Public token not found');
            return Inertia::render('Trace/Blocked', [
                'title' => 'QR không hợp lệ',
                'message' => 'QR công khai không tồn tại hoặc đã bị thu hồi.',
            ]);
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');

        // Public bắt buộc GPS
        if ($lat === null || $lng === null) {
            $this->logScan($request, $qr, null, null, null, 'denied_no_location', 'no_location');
            $this->alert($qr, 'denied_no_location', "Public QR quét nhưng không có GPS. place={$qr->place_name}");
            return Inertia::render('Trace/Blocked', [
                'title' => 'Cần bật định vị',
                'message' => 'QR công khai chỉ hợp lệ khi bật định vị để xác thực điểm phát hành.',
            ]);
        }

        // Public cần config
        if ($qr->allowed_lat === null || $qr->allowed_lng === null || $qr->allowed_radius_m === null) {
            $this->logScan($request, $qr, $lat, $lng, null, 'blocked', 'public_not_configured');
            $this->alert($qr, 'public_not_configured', "Public QR chưa cấu hình tọa độ/radius.");
            return Inertia::render('Trace/Blocked', [
                'title' => 'QR chưa cấu hình',
                'message' => 'QR công khai chưa được cấu hình điểm phát hành.',
            ]);
        }

        $distance = (int) round($this->distanceMeters(
            (float)$lat, (float)$lng,
            (float)$qr->allowed_lat, (float)$qr->allowed_lng
        ));

        $ok = $distance <= (int)$qr->allowed_radius_m;

        if (!$ok) {
            $this->logScan($request, $qr, $lat, $lng, $distance, 'blocked', 'geo_mismatch');
            $this->alert($qr, 'geo_mismatch', "Sai vị trí. expected={$qr->place_name}, distance={$distance}m, radius={$qr->allowed_radius_m}m");

            return Inertia::render('Trace/Blocked', [
                'title' => 'Sai vị trí quét',
                'message' => "QR công khai chỉ hợp lệ trong phạm vi {$qr->allowed_radius_m}m quanh '{$qr->place_name}'. Hệ thống đã ghi nhận cảnh báo.",
                'distance_m' => $distance,
                'radius_m' => (int)$qr->allowed_radius_m,
            ]);
        }

        $this->logScan($request, $qr, $lat, $lng, $distance, 'allowed', null);

        // Show published events (nếu ba đã có TraceEvent)
        $batch = $qr->batch;

        $events = class_exists(\App\Models\TraceEvent::class)
            ? \App\Models\TraceEvent::query()
                ->where('enterprise_id', $batch->enterprise_id)
                ->where('batch_id', $batch->id)
                ->where('status', 'published')
                ->orderBy('event_time')
                ->get(['event_type','event_time','location','note','content_hash','tx_hash','published_at'])
            : collect();

        return Inertia::render('Trace/Show', [
            'mode' => 'public',
            'batch' => $batch,
            'events' => $events,
            'place_name' => $qr->place_name,
        ]);
    }

    public function resolvePrivate(Request $request, string $token)
    {
        $qr = Qrcode::with('batch:id,code,product_name,enterprise_id')
            ->where('type', 'private')
            ->where('token', $token)
            ->first();

        if (!$qr) {
            $this->logInvalid($request, 'private', $token, 'token_not_found_or_deleted');
            $this->alertInvalid($token, 'private', 'invalid_token', 'Private token not found (expired/deleted)');
            return Inertia::render('Trace/Blocked', [
                'title' => 'QR không hợp lệ',
                'message' => 'QR riêng tư không tồn tại hoặc đã hết hiệu lực.',
            ]);
        }

        $now = now();

        // hết hạn -> log + alert + xóa record QR (token không còn tồn tại)
        if ($qr->expires_at && $now->greaterThan($qr->expires_at)) {
            $this->logScan($request, $qr, $request->input('lat'), $request->input('lng'), null, 'expired', 'private_expired');
            $this->alert($qr, 'private_expired', "Private QR hết hạn 48h. token={$token}");

            $qr->delete();

            return Inertia::render('Trace/Blocked', [
                'title' => 'QR đã hết hiệu lực',
                'message' => 'QR riêng tư chỉ có hiệu lực 48 giờ kể từ lần quét đầu tiên.',
            ]);
        }

        // lần đầu quét -> set first + expires +48h
        if (!$qr->first_scanned_at) {
            $qr->first_scanned_at = $now;
            $qr->expires_at = $now->copy()->addHours(48);
            $qr->save();
        }

        $this->logScan($request, $qr, $request->input('lat'), $request->input('lng'), null, 'allowed', null);

        $batch = $qr->batch;

        $events = class_exists(\App\Models\TraceEvent::class)
            ? \App\Models\TraceEvent::query()
                ->where('enterprise_id', $batch->enterprise_id)
                ->where('batch_id', $batch->id)
                ->where('status', 'published')
                ->orderBy('event_time')
                ->get(['event_type','event_time','location','note','content_hash','tx_hash','published_at'])
            : collect();

        return Inertia::render('Trace/Show', [
            'mode' => 'private',
            'batch' => $batch,
            'events' => $events,
            'expires_at' => optional($qr->expires_at)->toDateTimeString(),
        ]);
    }

    private function logInvalid(Request $request, string $type, string $token, string $reason): void
    {
        QrScanLog::create([
            'qrcode_id' => null,
            'enterprise_id' => null,
            'batch_id' => null,

            'qr_type' => $type,
            'token' => $token,

            'expected_place_name' => null,
            'expected_lat' => null,
            'expected_lng' => null,
            'expected_radius_m' => null,

            'scanned_at' => now(),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'distance_m' => null,

            'device_name' => $request->input('device_name'),
            'device_platform' => $request->input('device_platform'),
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),

            'decision' => 'invalid',
            'reason' => $reason,
        ]);
    }

    private function logScan(Request $request, Qrcode $qr, $lat, $lng, $distance, string $decision, ?string $reason): void
    {
        QrScanLog::create([
            'qrcode_id' => $qr->id,
            'enterprise_id' => $qr->enterprise_id,
            'batch_id' => $qr->batch_id,

            'qr_type' => $qr->type,
            'token' => $qr->token,

            'expected_place_name' => $qr->type === 'public' ? $qr->place_name : null,
            'expected_lat' => $qr->type === 'public' ? $qr->allowed_lat : null,
            'expected_lng' => $qr->type === 'public' ? $qr->allowed_lng : null,
            'expected_radius_m' => $qr->type === 'public' ? $qr->allowed_radius_m : null,

            'scanned_at' => now(),
            'lat' => $lat,
            'lng' => $lng,
            'distance_m' => $distance,

            'device_name' => $request->input('device_name'),
            'device_platform' => $request->input('device_platform'),
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),

            'decision' => $decision,
            'reason' => $reason,
        ]);
    }

    private function alert(Qrcode $qr, string $type, string $message): void
    {
        Alert::create([
            'enterprise_id' => $qr->enterprise_id,
            'batch_id' => $qr->batch_id,
            'qrcode_id' => $qr->id,
            'qr_type' => $qr->type,
            'token' => $qr->token,
            'type' => $type,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    private function alertInvalid(string $token, string $qrType, string $type, string $message): void
    {
        Alert::create([
            'enterprise_id' => null,
            'batch_id' => null,
            'qrcode_id' => null,
            'qr_type' => $qrType,
            'token' => $token,
            'type' => $type,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth * $c;
    }
}
