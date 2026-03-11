<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\Product;
use App\Models\TraceEvent;
use App\Traits\HasTenant;
use App\Services\IpfsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BatchTransformationController extends Controller
{
    use HasTenant;

    protected IpfsService $ipfs;

    public function __construct(IpfsService $ipfs)
    {
        $this->ipfs = $ipfs;
    }

    // ── Hiển thị form chế biến ────────────────────────────
    public function show(Request $request)
    {
        $tenantId = $this->tenantId($request);

        // Lô nguyên liệu khả dụng (đang active, đã nhận hoặc tự tạo)
        $batches = Batch::where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->with('product:id,name')
            ->get(['id', 'code', 'product_name', 'product_id',
                   'current_quantity', 'quantity', 'unit', 'certifications'])
            ->map(fn($b) => [
                'id'               => $b->id,
                'code'             => $b->code,
                'product_name'     => $b->product?->name ?? $b->product_name,
                'current_quantity' => $b->current_quantity ?? $b->quantity ?? 0,
                'unit'             => $b->unit ?? '',
                'certifications'   => $b->certifications ?? [],
            ]);

        // Danh sách sản phẩm để chọn đầu ra
        $products = Product::where('enterprise_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'gtin']);

        return Inertia::render('Batches/Transform', [
            'availableBatches' => $batches,
            'products'         => $products,
        ]);
    }

    // ── Xử lý chế biến ───────────────────────────────────
    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'input_batch_ids'       => 'required|array|min:1',
            'input_batch_ids.*'     => 'required|integer|exists:batches,id',
            'output_product_id'     => 'required|integer|exists:products,id',
            'loss_rate'             => 'required|numeric|min:0|max:99.99',
            'note'                  => 'nullable|string|max:500',
        ]);

        // Load tất cả lô đầu vào
        $inputBatches = Batch::whereIn('id', $data['input_batch_ids'])
            ->where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->get();

        if ($inputBatches->count() !== count($data['input_batch_ids'])) {
            return back()->withErrors(['input_batch_ids' => 'Một số lô không hợp lệ hoặc không thuộc doanh nghiệp.']);
        }

        // ── Kiểm tra chứng chỉ phải trùng nhau ──────────────
        $allCerts = $inputBatches->map(fn($b) => collect($b->certifications ?? [])->sort()->values()->toArray());
        $firstCerts = $allCerts->first();
        $certsMatch = $allCerts->every(fn($certs) => $certs === $firstCerts);

        if (!$certsMatch) {
            $certsSummary = $inputBatches->map(fn($b) => $b->code . ': ' . implode(', ', $b->certifications ?? ['Không có']))->implode(' | ');
            return back()->withErrors([
                'input_batch_ids' => "Các lô nguyên liệu phải có cùng chứng chỉ/tiêu chuẩn. Hiện tại: {$certsSummary}",
            ]);
        }

        // ── Tính sản lượng đầu ra ────────────────────────────
        $totalInputQty = $inputBatches->sum(fn($b) => $b->current_quantity ?? $b->quantity ?? 0);
        $lossRate      = $data['loss_rate'] / 100;
        $outputQty     = round($totalInputQty * (1 - $lossRate), 2);
        $unit          = $inputBatches->first()->unit ?? 'kg';

        // Load sản phẩm đầu ra
        $outputProduct = Product::where('id', $data['output_product_id'])
            ->where('enterprise_id', $tenantId)
            ->firstOrFail();

        DB::transaction(function () use ($data, $inputBatches, $totalInputQty, $outputQty, $unit, $outputProduct, $tenantId, $firstCerts) {

            // ── Sinh mã lô thành phẩm ────────────────────────
            $firstCode = $inputBatches->first()->code;
            $seq       = str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
            $newCode   = $firstCode . '-T' . $seq;

            // ── Tạo lô thành phẩm ────────────────────────────
            $outputBatch = Batch::create([
                'enterprise_id'        => $tenantId,
                'origin_enterprise_id' => $tenantId,
                'product_id'           => $outputProduct->id,
                'product_name'         => $outputProduct->name,
                'code'                 => $newCode,
                'batch_type'           => 'transformed',
                'quantity'             => $outputQty,
                'current_quantity'     => $outputQty,
                'unit'                 => $unit,
                'certifications'       => $firstCerts,
                'status'               => 'active',
            ]);

            // ── Tạo sự kiện chế biến ─────────────────────────
            $event = TraceEvent::create([
                'enterprise_id' => $tenantId,
                'event_type'    => 'transformation',
                'cte_code'      => 'transformation',
                'kde_data'      => [
                    'action'          => 'transformation',
                    'input_batches'   => $inputBatches->map(fn($b) => [
                        'id'       => $b->id,
                        'code'     => $b->code,
                        'product'  => $b->product_name,
                        'quantity' => $b->current_quantity ?? $b->quantity ?? 0,
                        'unit'     => $b->unit,
                    ]),
                    'total_input_qty' => $totalInputQty,
                    'loss_rate'       => $data['loss_rate'],
                    'output_qty'      => $outputQty,
                    'output_product'  => $outputProduct->name,
                ],
                'who_name'   => auth()->user()->name,
                'note'       => $data['note'] ?? null,
                'status'     => 'draft',
                'event_time' => now(),
            ]);

            // Gắn lô đầu ra vào pivot event_output_batches
            $event->outputBatches()->attach($outputBatch->id, [
                'quantity' => $outputQty,
                'unit'     => $unit,
            ]);

            // Gắn lô đầu vào vào pivot event_input_batches
            foreach ($inputBatches as $inputBatch) {
                $event->inputBatches()->attach($inputBatch->id, [
                    'quantity' => $inputBatch->current_quantity ?? $inputBatch->quantity ?? 0,
                    'unit'     => $inputBatch->unit,
                ]);
            }

            // ── Ghi lineage cho từng lô input ────────────────
            foreach ($inputBatches as $inputBatch) {
                BatchLineage::create([
                    'transformation_type' => 'transformation',
                    'input_batch_id'      => $inputBatch->id,
                    'output_batch_id'     => $outputBatch->id,
                    'quantity'            => $inputBatch->current_quantity ?? $inputBatch->quantity ?? 0,
                    'unit'                => $inputBatch->unit,
                    'event_id'            => $event->id,
                ]);

                // Đánh dấu lô input đã tiêu thụ
                $inputBatch->update([
                    'current_quantity' => 0,
                    'status'           => 'consumed',
                ]);
            }

            // ── Auto-publish lên IPFS ────────────────────────
            $this->autoPublishEvent($event);
        });

        return redirect()->route('batches.index')
            ->with('success', "Đã chế biến thành công → Lô thành phẩm {$outputProduct->name} ({$outputQty} {$unit}).");
    }

    // ── Helper: publish event lên IPFS ───────────────────
    private function autoPublishEvent(TraceEvent $event): void
    {
        try {
            $payload     = $event->toIpfsPayload();
            $contentHash = hash('sha256', json_encode($payload));
            $ipfsResult  = $this->ipfs->uploadJson($payload);

            $updateData = [
                'status'       => 'published',
                'content_hash' => $contentHash,
                'published_at' => now(),
            ];

            if ($ipfsResult) {
                $updateData['ipfs_cid'] = $ipfsResult['cid'];
                $updateData['ipfs_url'] = $ipfsResult['url'];
            }

            $event->update($updateData);
        } catch (\Throwable $e) {
            \Log::error('auto-publish transformation event failed', [
                'event_id' => $event->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
