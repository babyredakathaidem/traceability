<?php

namespace App\Http\Controllers;

use App\Mail\TransferAcceptedMail;
use App\Mail\TransferSentMail;
use App\Models\Batch;
use App\Models\BatchTransfer;
use App\Models\Enterprise;
use App\Models\TraceEvent;
use App\Models\User;
use App\Services\BlockchainService;
use App\Services\IpfsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class BatchTransferController extends Controller
{
    public function __construct(
        private IpfsService $ipfs,
        private BlockchainService $blockchain,
    ) {}

    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    // ── Inbox (DN B xem danh sách transfer đến) ───────────
    public function inbox(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $transfers = BatchTransfer::with([
            'batch:id,code,product_name,quantity,unit',
            'fromEnterprise:id,name,code',
        ])
            ->where('to_enterprise_id', $tenantId)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return Inertia::render('Batches/TransferInbox', [
            'transfers' => $transfers,
        ]);
    }

    // ── DN A gửi yêu cầu chuyển giao ─────────────────────
    public function store(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless(in_array($batch->status, ['active', 'received']), 422);

        if ($batch->pendingTransfer()->exists()) {
            return back()->withErrors(['error' => 'Lô này đang có yêu cầu chuyển giao chờ xác nhận.']);
        }

        $data = $request->validate([
            'to_enterprise_code' => 'required|string|exists:enterprises,code',
            'quantity'           => 'required|integer|min:1',
            'invoice_no'         => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:500',
        ]);

        $toEnterprise = Enterprise::where('code', $data['to_enterprise_code'])
            ->where('status', 'approved')
            ->first();

        if (!$toEnterprise) {
            return back()->withErrors(['to_enterprise_code' => 'Doanh nghiệp không tồn tại hoặc chưa được duyệt.']);
        }

        if ($toEnterprise->id === $tenantId) {
            return back()->withErrors(['to_enterprise_code' => 'Không thể chuyển giao cho chính mình.']);
        }

        $available = $batch->current_quantity ?? $batch->quantity;
        if ($data['quantity'] > $available) {
            return back()->withErrors(['quantity' => "Số lượng vượt quá hiện có ({$available})."]);
        }

        $transfer = BatchTransfer::create([
            'batch_id'           => $batch->id,
            'from_enterprise_id' => $tenantId,
            'to_enterprise_id'   => $toEnterprise->id,
            'quantity'           => $data['quantity'],
            'unit'               => $batch->unit,
            'invoice_no'         => $data['invoice_no'] ?? null,
            'note'               => $data['note'] ?? null,
            'status'             => 'pending',
            'transferred_at'     => now(),
        ]);

        // ── Gửi mail thông báo cho admin DN B ─────────────
        $this->notifyEnterpriseAdmins(
            $toEnterprise->id,
            new TransferSentMail($transfer->load(['batch', 'fromEnterprise', 'toEnterprise']))
        );

        return back()->with('success', "Đã gửi yêu cầu chuyển giao lô {$batch->code} cho {$toEnterprise->name}. Email thông báo đã gửi.");
    }

    // ── DN B xác nhận nhận hàng ───────────────────────────
    public function accept(Request $request, BatchTransfer $transfer)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($transfer->to_enterprise_id === $tenantId, 403);
        abort_unless($transfer->isPending(), 422);

        $acceptedBy = $request->user()->name;

        DB::transaction(function () use ($transfer, $tenantId, $acceptedBy) {
            // withoutGlobalScopes — batch đang thuộc DN A, bỏ qua TenantScope
            $batch = Batch::withoutGlobalScopes()->findOrFail($transfer->batch_id);
            $batch->load('enterprise');

            $fromEnterpriseName = $transfer->fromEnterprise->name;
            $toEnterpriseName   = $transfer->toEnterprise->name;

            // ── Event 1: DN A — Chuyển giao lô ──────────────
            $eventSent = TraceEvent::create([
                'batch_id'      => $batch->id,
                'enterprise_id' => $transfer->from_enterprise_id,
                'event_type'    => 'transfer_sent',
                'cte_code'      => 'transfer_sent',
                'kde_data'      => [
                    'action'        => 'transfer_sent',
                    'to_enterprise' => $toEnterpriseName,
                    'quantity'      => $transfer->quantity,
                    'unit'          => $transfer->unit,
                    'invoice_no'    => $transfer->invoice_no,
                ],
                'who_name'   => $fromEnterpriseName,
                'note'       => "Chuyển giao lô cho {$toEnterpriseName}",
                'status'     => 'draft',
                'event_time' => $transfer->transferred_at ?? now(),
            ]);

            // ── Event 2: DN B — Nhận lô ──────────────────────
            $eventReceived = TraceEvent::create([
                'batch_id'      => $batch->id,
                'enterprise_id' => $tenantId,
                'event_type'    => 'transfer_received',
                'cte_code'      => 'transfer_received',
                'kde_data'      => [
                    'action'          => 'transfer_accepted',
                    'from_enterprise' => $fromEnterpriseName,
                    'to_enterprise'   => $toEnterpriseName,
                    'quantity'        => $transfer->quantity,
                    'unit'            => $transfer->unit,
                    'invoice_no'      => $transfer->invoice_no,
                ],
                'who_name'   => $acceptedBy,
                'note'       => "Nhận hàng từ {$fromEnterpriseName}",
                'status'     => 'draft',
                'event_time' => now(),
            ]);

            // ── Auto-publish cả 2 events lên IPFS + Fabric ───
            $this->autoPublishEvent($eventSent, $batch);
            $this->autoPublishEvent($eventReceived, $batch);

            // ── Cập nhật transfer ─────────────────────────────
            $transfer->update([
                'status'            => 'accepted',
                'accepted_at'       => now(),
                'transfer_event_id' => $eventReceived->id,
            ]);

            // ── Chuyển lô sang DN B ───────────────────────────
            $batch->update([
                'enterprise_id' => $tenantId,
                'batch_type'    => 'received',
                'status'        => 'received',
            ]);
        });

        // ── Gửi mail thông báo cho DN A ───────────────────
        $transfer->refresh()->load(['batch', 'fromEnterprise', 'toEnterprise']);
        $this->notifyEnterpriseAdmins(
            $transfer->from_enterprise_id,
            new TransferAcceptedMail($transfer)
        );

        return redirect()->route('batches.index')
            ->with('success', 'Đã xác nhận nhận hàng thành công. Sự kiện đã được publish tự động.');
    }

    // ── DN B từ chối ──────────────────────────────────────
    public function reject(Request $request, BatchTransfer $transfer)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($transfer->to_enterprise_id === $tenantId, 403);
        abort_unless($transfer->isPending(), 422);

        $data = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $transfer->update([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu chuyển giao.');
    }

    public function show(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        return Inertia::render('Batches/Transfer', [
            'batch' => [
                'id'               => $batch->id,
                'code'             => $batch->code,
                'product_name'     => $batch->getDisplayName(),
                'current_quantity' => $batch->current_quantity ?? $batch->quantity,
                'unit'             => $batch->unit,
                'status'           => $batch->status,
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────

    /**
     * Auto-publish event lên IPFS + Fabric (non-blocking).
     * Lỗi chỉ log, không rollback transaction.
     */
    private function autoPublishEvent(TraceEvent $event, Batch $batch): void
    {
        try {
            $batch->loadMissing(['enterprise', 'product']);

            $payload = [
                'system'       => 'AGU Traceability',
                'version'      => '1.0',
                'tcvn_ref'     => 'TCVN 12850:2019',
                'event_id'     => $event->id,
                'event_type'   => $event->event_type,
                'cte_code'     => $event->cte_code,
                'batch_code'   => $batch->code,
                'product_name' => $batch->product_name,
                'enterprise'   => $batch->enterprise?->name,
                'event_time'   => $event->event_time?->toISOString(),
                'who_name'     => $event->who_name,
                'where'        => $event->where_address,
                'kde_data'     => $event->kde_data ?? [],
                'note'         => $event->note,
            ];

            $contentHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
            $ipfsResult  = $this->ipfs->uploadJson($payload);

            if (!$ipfsResult['success']) {
                Log::warning("AutoPublish IPFS failed event#{$event->id}", $ipfsResult);
                return;
            }

            $ipfsCid = $ipfsResult['cid'];
            $txHash  = null;

            try {
                $fabricResult = $this->blockchain->recordEvent(
                    (string) $event->id,
                    $batch->code,
                    $batch->enterprise?->code ?? (string) $event->enterprise_id,
                    $event->cte_code,
                    $contentHash,
                    $ipfsCid,
                    $event->who_name ?? 'system',
                );
                if ($fabricResult['success']) {
                    $txHash = $fabricResult['data']['txId']
                        ?? $fabricResult['data']['tx_hash']
                        ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning("AutoPublish Fabric failed event#{$event->id}: " . $e->getMessage());
            }

            $event->update([
                'status'       => 'published',
                'content_hash' => $contentHash,
                'ipfs_cid'     => $ipfsCid,
                'ipfs_url'     => "https://gateway.pinata.cloud/ipfs/{$ipfsCid}",
                'tx_hash'      => $txHash,
                'published_by' => $event->enterprise_id,
                'published_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("AutoPublish failed event#{$event->id}: " . $e->getMessage());
        }
    }

    /**
     * Gửi mail cho tất cả enterprise_admin của 1 DN.
     */
    private function notifyEnterpriseAdmins(int $enterpriseId, $mailable): void
    {
        try {
            $admins = User::where('enterprise_id', $enterpriseId)
                ->where('role', 'enterprise_admin')
                ->get(['email']);

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue($mailable);
            }
        } catch (\Throwable $e) {
            Log::warning("notifyEnterpriseAdmins failed: " . $e->getMessage());
        }
    }
}