<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\CteTemplate;
use App\Models\TraceEvent;
use App\Services\IpfsService;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Mail\EventPublishedMail;
use Illuminate\Support\Facades\Mail;

class TraceEventController extends Controller
{
    public function __construct(
        private IpfsService       $ipfs,
        private BlockchainService $blockchain,
    ) {}

    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    // ── index ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $batchId  = $request->query('batch_id');

        $batches = Batch::with('product:id,name,category_id')
            ->where('enterprise_id', $tenantId)
            ->whereNotIn('status', ['consumed', 'recalled'])
            ->orderByDesc('id')
            ->get(['id', 'code', 'product_id', 'product_name', 'status',
                'batch_type', 'certifications', 'current_quantity', 'unit']);

        $events = TraceEvent::with([
            'inputBatches:id,code,product_id,product_name',
            'inputBatches.product:id,name,gtin',
            'publisher:id,name',
        ])
            ->whereHas('inputBatches', fn($q) => $q->where('enterprise_id', $tenantId))
            ->when($batchId, fn($q) => $q->whereHas('inputBatches', fn($q2) => $q2->where('batches.id', $batchId)))
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Events/Index', [
            'batches' => $batches->map(fn($b) => [
                'id'               => $b->id,
                'code'             => $b->code,
                'product_id'       => $b->product_id,
                'product_name'     => $b->product?->name ?? $b->product_name,
                'status'           => $b->status,
                'batch_type'       => $b->batch_type,
                'certifications'   => $b->certifications ?? [],
                'current_quantity' => $b->current_quantity,
                'unit'             => $b->unit,
                'category_id'      => $b->product?->category_id ?? null,
            ]),
            'events'  => $events,
            'filters' => ['batch_id' => $batchId],
        ]);
    }

    // ── getTemplates (API) ────────────────────────────────

    public function getTemplates(Request $request)
    {
        $categoryId = $request->query('category_id');
        $batchId    = $request->query('batch_id');

        $templates = collect();

        // 1. Nếu có batchId -> Lấy quy trình riêng của Sản phẩm thuộc Lô đó
        if ($batchId) {
            $batch = Batch::with('product.processes')->find($batchId);
            if ($batch && $batch->product && $batch->product->processes->isNotEmpty()) {
                $templates = $batch->product->processes->map(fn($p) => (object)[
                    'id'          => $p->id,
                    'code'        => $p->cte_code ?? 'step-' . $p->id,
                    'name_vi'     => $p->name_vi,
                    'step_order'  => $p->step_order,
                    'is_required' => $p->is_required,
                    'kde_schema'  => [], // Có thể mở rộng schema cho từng bước sau
                ]);
            }
        }

        // 2. Fallback về Category Templates nếu chưa có templates từ sản phẩm
        if ($templates->isEmpty() && $categoryId) {
            $templates = CteTemplate::where('category_id', $categoryId)
                ->orderBy('step_order')
                ->get();
        }

        $publishedCodes = [];
        if ($batchId) {
            $publishedCodes = TraceEvent::whereHas('inputBatches', fn($q) => $q->where('batches.id', $batchId))
                ->where('status', 'published')
                ->whereNotNull('cte_code')
                ->pluck('cte_code')
                ->unique()
                ->toArray();
        }

        return response()->json([
            'templates'      => $templates->map(fn($t) => [
                'id'          => $t->id,
                'code'        => $t->code,
                'name_vi'     => $t->name_vi,
                'step_order'  => $t->step_order,
                'is_required' => $t->is_required,
                'kde_schema'  => $t->kde_schema ?? [],
                'is_done'     => in_array($t->code, $publishedCodes),
            ]),
            'published_codes' => $publishedCodes,
            'has_templates'   => $templates->isNotEmpty(),
        ]);
    }

    // ── store (draft) ─────────────────────────────────────

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'batch_id'     => 'required|integer|exists:batches,id',
            'cte_code'     => 'required|string|max:60',
            'event_time'   => 'required|date',
            'kde_data'     => 'required|array',
            'who_name'     => 'nullable|string|max:255',
            'where_address'=> 'nullable|string|max:255',
            'where_lat'    => 'nullable|numeric|between:-90,90',
            'where_lng'    => 'nullable|numeric|between:-180,180',
            'why_reason'   => 'nullable|string|max:255',
            'note'         => 'nullable|string|max:2000',
        ]);

        $batch = Batch::where('id', $data['batch_id'])
            ->where('enterprise_id', $tenantId)
            ->firstOrFail();

        if ($batch->isRecalled()) {
            return back()->withErrors(['batch' => 'Lô này đang bị thu hồi, không thể thêm sự kiện mới.']);
        }

        $event = TraceEvent::create([
            'enterprise_id' => $tenantId,
            'cte_code'      => $data['cte_code'],
            'event_type'    => $data['cte_code'],
            'event_time'    => $data['event_time'],
            'kde_data'      => $data['kde_data'],
            'who_name'      => $data['who_name'] ?? null,
            'where_address' => $data['where_address'] ?? null,
            'where_lat'     => $data['where_lat'] ?? null,
            'where_lng'     => $data['where_lng'] ?? null,
            'why_reason'    => $data['why_reason'] ?? null,
            'note'          => $data['note'] ?? null,
            'status'        => 'draft',
        ]);
        // Liên kết lô với event qua pivot (batch_id đã bị xóa khỏi trace_events)
        $event->inputBatches()->attach($batch->id);

        return back()->with('success', 'Đã lưu sự kiện (draft). Kiểm tra lại rồi publish lên IPFS.');
    }

    // ── uploadAttachment ──────────────────────────────────

    public function uploadAttachment(Request $request, TraceEvent $traceEvent)
    {
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            return response()->json(['error' => 'Sự kiện đã publish, không thể thêm đính kèm.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
        ]);

        $file     = $request->file('file');
        $content  = file_get_contents($file->getRealPath());
        $filename = $file->getClientOriginalName();
        $mime     = $file->getMimeType();

        $result = $this->ipfs->uploadFile($content, $filename, $mime);

        if (!$result) {
            return response()->json(['error' => 'Upload IPFS thất bại.'], 500);
        }

        $attachments   = $traceEvent->attachments ?? [];
        $attachments[] = [
            'cid'       => $result['cid'],
            'url'       => $result['url'],
            'name'      => $filename,
            'mime_type' => $mime,
            'mock'      => $result['mock'],
        ];

        $traceEvent->update(['attachments' => $attachments]);

        return response()->json(['attachment' => end($attachments)]);
    }

    // ── update ─────────────────────────────────────────────

    public function update(Request $request, TraceEvent $traceEvent)
    {
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            abort(403, 'Sự kiện đã publish lên IPFS, không thể sửa.');
        }

        $data = $request->validate([
            'cte_code'     => 'required|string|max:60',
            'event_time'   => 'required|date',
            'kde_data'     => 'required|array',
            'who_name'     => 'nullable|string|max:255',
            'where_address'=> 'nullable|string|max:255',
            'where_lat'    => 'nullable|numeric|between:-90,90',
            'where_lng'    => 'nullable|numeric|between:-180,180',
            'why_reason'   => 'nullable|string|max:255',
            'note'         => 'nullable|string|max:2000',
        ]);

        $traceEvent->update($data);

        return back()->with('success', 'Đã cập nhật sự kiện.');
    }

    // ── destroy ────────────────────────────────────────────

    public function destroy(Request $request, TraceEvent $traceEvent)
    {
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            abort(403, 'Sự kiện đã publish lên IPFS, không thể xóa.');
        }

        $traceEvent->delete();

        return back()->with('success', 'Đã xóa sự kiện.');
    }

    // ── publish → IPFS + Hyperledger Fabric ───────────────

    /**
     * POST /events/{traceEvent}/publish
     *
     * Luồng dual-immutability:
     *  1. Build payload + SHA-256 content hash
     *  2. Upload JSON lên IPFS (Pinata) → CID (content-addressed)
     *  3. Ghi CID + hash lên Hyperledger Fabric → tx_hash (tamper-evident ledger)
     *  4. Lưu DB: status=published, ipfs_cid, tx_hash, content_hash
     *
     * Non-blocking design:
     *  - Nếu Fabric gateway timeout/lỗi → vẫn publish thành công (IPFS đã OK)
     *  - tx_hash = null được phép, ghi log warning để retry sau
     *  - Không block UX vì Fabric network có thể chậm
     */
    public function publish(Request $request, TraceEvent $traceEvent)
    {
        $traceEvent->loadMissing('inputBatches.product.category', 'inputBatches.enterprise');
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            return back()->with('success', 'Sự kiện này đã được publish rồi.');
        }

        // ── 1. Payload + content hash ──────────────────────
        $payload     = $traceEvent->toIpfsPayload();
        $json        = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $contentHash = hash('sha256', $json);

        // ── 2. Upload lên IPFS ─────────────────────────────
        $ipfsResult = $this->ipfs->uploadJson(
            $payload,
            "event-{$traceEvent->id}-{$traceEvent->cte_code}"
        );

        if (!$ipfsResult) {
            return back()->withErrors(['ipfs' => 'Không thể upload lên IPFS. Vui lòng thử lại.']);
        }

        // ── 3. Ghi lên Hyperledger Fabric (non-blocking) ───
        $txHash      = null;
        $fabricOk    = false;
        $fabricError = null;

        try {
            $fabricResult = $this->blockchain->recordEvent(
                eventID:      (string) $traceEvent->id,
                batchCode:    (string) ($traceEvent->inputBatches->first()?->code ?? ''),
                enterpriseID: (string) ($traceEvent->inputBatches->first()?->enterprise?->code ?? ''),
                cteCode:      (string) $traceEvent->cte_code,
                contentHash:  $contentHash,
                ipfsCid:      $ipfsResult['cid'],
                recordedBy:   $request->user()->name,
            );

            if ($fabricResult['success']) {
                $fabricOk = true;
                // Gateway trả txId theo nhiều tên khác nhau
                $txHash = $fabricResult['data']['txId']
                    ?? $fabricResult['data']['tx_hash']
                    ?? $fabricResult['data']['transactionId']
                    ?? null;

                Log::info('[Publish] Fabric OK', [
                    'event_id' => $traceEvent->id,
                    'tx_hash'  => $txHash,
                    'cid'      => $ipfsResult['cid'],
                ]);
            } else {
                $fabricError = $fabricResult['error'] ?? 'unknown';
                Log::warning('[Publish] Fabric failed — non-blocking, continuing', [
                    'event_id' => $traceEvent->id,
                    'error'    => $fabricError,
                    'cid'      => $ipfsResult['cid'],
                ]);
            }
        } catch (\Throwable $e) {
            $fabricError = $e->getMessage();
            Log::error('[Publish] Fabric exception — non-blocking', [
                'event_id' => $traceEvent->id,
                'exception'=> $e->getMessage(),
                'cid'      => $ipfsResult['cid'],
            ]);
        }

        // ── 4. Cập nhật DB ─────────────────────────────────
        $traceEvent->update([
            'status'       => 'published',
            'content_hash' => $contentHash,
            'ipfs_cid'     => $ipfsResult['cid'],
            'ipfs_url'     => $ipfsResult['url'],
            'tx_hash'      => $txHash,   // null nếu Fabric lỗi
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        // ── 5. Gửi email xác nhận ──────────────────────────
        Mail::to($request->user()->email)->queue(
            new EventPublishedMail($traceEvent->fresh(['inputBatches.product']))
        );

        // ── 6. Flash message ───────────────────────────────
        $ipfsMock = $ipfsResult['mock'] ? ' [MOCK]' : '';

        if ($fabricOk && $txHash) {
            $shortTx = substr($txHash, 0, 16) . '...';
            $msg = " Publish thành công{$ipfsMock}! "
                . "IPFS CID: " . substr($ipfsResult['cid'], 0, 12) . "... · "
                . "Fabric TX: {$shortTx}";
        } elseif ($fabricOk) {
            $msg = "Publish thành công{$ipfsMock}! IPFS + Fabric OK.";
        } else {
            $msg = " Publish IPFS thành công{$ipfsMock}! CID: " . substr($ipfsResult['cid'], 0, 16)
                . "... Fabric chưa ghi được (tx_hash sẽ được cập nhật sau).";
        }

        return back()->with('success', $msg);
    }

    // ── verifyIpfs (public endpoint) ──────────────────────

    public function verifyIpfs(Request $request, string $cid)
    {
        $expectedHash = $request->query('hash', '');

        if (!$expectedHash) {
            return response()->json(['error' => 'Thiếu hash để xác minh.'], 400);
        }

        $result = $this->ipfs->verify($cid, $expectedHash);

        return response()->json([
            'cid'           => $cid,
            'valid'         => $result['valid'],
            'fetched'       => $result['fetched'],
            'expected_hash' => $result['expected_hash'],
            'actual_hash'   => $result['actual_hash'],
            'mock'          => $result['mock'],
        ]);
    }

    // ── Private ────────────────────────────────────────────

    private function assertTenant(Request $request, TraceEvent $event): void
    {
        abort_unless(
            (int) $event->enterprise_id === $this->tenantId($request),
            403
        );
    }
}