<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchTransfer;
use App\Models\Enterprise;
use App\Models\TraceEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BatchTransferController extends Controller
{

    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }
    // Danh sách transfer đến DN mình (inbox)
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

    // DN A gửi yêu cầu chuyển giao
    public function store(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless(in_array($batch->status, ['active', 'received']), 422);

        // Không cho transfer nếu đang có pending transfer
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

        BatchTransfer::create([
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

        return back()->with('success', "Đã gửi yêu cầu chuyển giao lô {$batch->code} cho {$toEnterprise->name}.");
    }

    // DN B xác nhận nhận hàng
    public function accept(Request $request, BatchTransfer $transfer)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($transfer->to_enterprise_id === $tenantId, 403);
        abort_unless($transfer->isPending(), 422);

        DB::transaction(function () use ($transfer, $tenantId) {
            $batch = $transfer->batch;

            // Tạo TransactionEvent publish IPFS
            $event = TraceEvent::create([
                'batch_id'      => $batch->id,
                'enterprise_id' => $tenantId,
                'cte_code'      => 'transfer_received',
                'kde_data'      => [
                    'action'           => 'transfer_accepted',
                    'from_enterprise'  => $transfer->fromEnterprise->name,
                    'to_enterprise'    => $transfer->toEnterprise->name,
                    'quantity'         => $transfer->quantity,
                    'unit'             => $transfer->unit,
                    'invoice_no'       => $transfer->invoice_no,
                ],
                'who_name'      => auth()->user()->name,
                'note'          => "Nhận hàng từ {$transfer->fromEnterprise->name}",
                'status'        => 'draft',
                'event_time'    => now(),
            ]);

            // Cập nhật transfer
            $transfer->update([
                'status'            => 'accepted',
                'accepted_at'       => now(),
                'transfer_event_id' => $event->id,
            ]);

            // Chuyển lô sang DN B
            $batch->update([
                'enterprise_id' => $tenantId,
                'batch_type'    => 'received',
                'status'        => 'received',
                // origin_enterprise_id KHÔNG đổi
            ]);
        });

        return redirect()->route('batches.index')
            ->with('success', 'Đã xác nhận nhận hàng thành công.');
    }

    // DN B từ chối
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
}