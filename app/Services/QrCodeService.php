<?php

namespace App\Services;

use App\Models\Qrcode;
use Illuminate\Support\Str;

class QrCodeService
{
    public function ensureForBatch(int $enterpriseId, int $batchId): void
    {
        foreach (['public','private'] as $type) {
            Qrcode::firstOrCreate(
                ['batch_id' => $batchId, 'type' => $type],
                [
                    'enterprise_id' => $enterpriseId,
                    'token' => Str::random(60),
                ]
            );
        }
    }
}
