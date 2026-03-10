<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    private string $gatewayUrl;

    public function __construct()
    {
        $this->gatewayUrl = config('services.fabric.gateway_url');
    }

    /**
     * Ghi sự kiện truy xuất lên Hyperledger Fabric
     */
    public function recordEvent(
        string $eventID,
        string $batchCode,
        string $enterpriseID,
        string $cteCode,
        string $contentHash,
        string $ipfsCid,
        string $recordedBy
    ): array {
        try {
            $response = Http::timeout(30)->post("{$this->gatewayUrl}/fabric/record-event", [
                'eventID'      => $eventID,
                'batchCode'    => $batchCode,
                'enterpriseID' => $enterpriseID,
                'cteCode'      => $cteCode,
                'contentHash'  => $contentHash,
                'ipfsCid'      => $ipfsCid,
                'recordedBy'   => $recordedBy,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::warning('Blockchain recordEvent failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'error' => $response->json('error')];

        } catch (\Exception $e) {
            Log::error('Blockchain gateway error', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Chuyển giao lô giữa các doanh nghiệp
     */
    public function transferBatch(
        string $transferID,
        string $batchCode,
        string $fromEnterprise,
        string $toEnterprise,
        string $invoiceNo = ''
    ): array {
        try {
            $response = Http::timeout(30)->post("{$this->gatewayUrl}/fabric/transfer-batch", [
                'transferID'     => $transferID,
                'batchCode'      => $batchCode,
                'fromEnterprise' => $fromEnterprise,
                'toEnterprise'   => $toEnterprise,
                'invoiceNo'      => $invoiceNo,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'error' => $response->json('error')];

        } catch (\Exception $e) {
            Log::error('Blockchain transferBatch error', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Xác minh content hash trên blockchain
     */
    public function verifyContentHash(string $eventID, string $expectedHash): bool
    {
        try {
            $response = Http::timeout(10)->get("{$this->gatewayUrl}/fabric/verify/{$eventID}/{$expectedHash}");
            return $response->successful() && $response->json('valid') === true;
        } catch (\Exception $e) {
            Log::error('Blockchain verify error', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Query event từ blockchain
     */
    public function getEvent(string $eventID): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->gatewayUrl}/fabric/event/{$eventID}");
            if ($response->successful()) {
                return $response->json();
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Blockchain getEvent error', ['message' => $e->getMessage()]);
            return null;
        }
    }
}