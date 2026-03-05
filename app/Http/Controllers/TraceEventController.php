<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\CteTemplate;
use App\Models\TraceEvent;
use App\Services\IpfsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Mail\EventPublishedMail;
use Illuminate\Support\Facades\Mail;

class TraceEventController extends Controller
{
    public function __construct(private IpfsService $ipfs) {}

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
            'batch:id,code,product_id,product_name',
            'batch.product:id,name,gtin',
            'publisher:id,name',
        ])
            ->whereHas('batch', fn($q) => $q->where('enterprise_id', $tenantId))
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
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

    /**
     * GET /api/cte-templates?category_id=X
     * Trả về danh sách CTE + completeness cho 1 batch cụ thể
     */
    public function getTemplates(Request $request)
    {
        $categoryId = $request->query('category_id');
        $batchId    = $request->query('batch_id');

        if ($categoryId) {
            $templates = CteTemplate::where('category_id', $categoryId)
                ->orderBy('step_order')
                ->get();
        } else {
            // Lô merged/split/received → frontend dùng GENERIC_TEMPLATES
            $templates = collect();
        }

        $publishedCodes = [];
        if ($batchId) {
            $publishedCodes = TraceEvent::where('batch_id', $batchId)
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
                'kde_schema'  => $t->kde_schema,
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

        // Đảm bảo batch thuộc đúng tenant
        $batch = Batch::where('id', $data['batch_id'])
            ->where('enterprise_id', $tenantId)
            ->firstOrFail();

        // Không cho ghi sự kiện mới vào lô đã bị thu hồi
        if ($batch->isRecalled()) {
            return back()->withErrors(['batch' => 'Lô này đang bị thu hồi, không thể thêm sự kiện mới.']);
        }

        TraceEvent::create([
            'enterprise_id' => $tenantId,
            'batch_id'      => $batch->id,
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
            'location'      => $data['where_address'] ?? null,
            'status'        => 'draft',
        ]);

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

    // ── publish → IPFS ─────────────────────────────────────

    public function publish(Request $request, TraceEvent $traceEvent)
    {
        $traceEvent->loadMissing('batch.product.category', 'batch.enterprise');
        $this->assertTenant($request, $traceEvent);

        if ($traceEvent->isPublished()) {
            return back()->with('success', 'Sự kiện này đã được publish rồi.');
        }

        // Tạo payload bất biến 5W
        $payload     = $traceEvent->toIpfsPayload();
        $json        = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $contentHash = hash('sha256', $json);

        // Upload lên IPFS
        $ipfsResult = $this->ipfs->uploadJson(
            $payload,
            "event-{$traceEvent->id}-{$traceEvent->cte_code}"
        );

        if (!$ipfsResult) {
            return back()->withErrors(['ipfs' => 'Không thể upload lên IPFS. Vui lòng thử lại.']);
        }

        $traceEvent->update([
            'status'       => 'published',
            'content_hash' => $contentHash,
            'ipfs_cid'     => $ipfsResult['cid'],
            'ipfs_url'     => $ipfsResult['url'],
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        Mail::to($request->user()->email)->queue(
            new EventPublishedMail($traceEvent->fresh(['batch.product']))
        );

        $mock = $ipfsResult['mock'] ? ' (MOCK - chưa cấu hình Pinata key)' : '';
        return back()->with('success', "Publish IPFS thành công{$mock}! CID: {$ipfsResult['cid']}");
    }

    // ── verify IPFS ────────────────────────────────────────

    /**
     * GET /verify/ipfs/{cid}?hash=xxx
     * Public endpoint — người dùng verify tính toàn vẹn
     */
    public function verifyIpfs(Request $request, string $cid)
    {
        $expectedHash = $request->query('hash', '');

        if (!$expectedHash) {
            return response()->json(['error' => 'Thiếu hash để xác minh.'], 400);
        }

        $result = $this->ipfs->verify($cid, $expectedHash);

        return response()->json([
            'cid'          => $cid,
            'valid'        => $result['valid'],
            'fetched'      => $result['fetched'],
            'expected_hash'=> $result['expected_hash'],
            'actual_hash'  => $result['actual_hash'],
            'mock'         => $result['mock'],
        ]);
    }

    // ── Private helpers ────────────────────────────────────

    /**
     * Kiểm tra event thuộc đúng tenant của user đang login.
     * Dùng enterprise_id trực tiếp trên event — tránh load batch qua global scope.
     */
    private function assertTenant(Request $request, TraceEvent $event): void
    {
        abort_unless(
            (int) $event->enterprise_id === $this->tenantId($request),
            403
        );
    }
}