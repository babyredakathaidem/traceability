<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BatchController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    // ── Prefix map theo category code ────────────────────
    private const CATEGORY_PREFIX = [
        'lua_gao'      => 'LG',
        'rau_qua'      => 'RQ',
        'thuy_san'     => 'TS',
        'chan_nuoi'     => 'CN',
        'thuc_pham_cb' => 'TP',
        'khac'         => 'KH',
    ];

    /**
     * Tự sinh mã lô: {PREFIX}{enterpriseId 2 chữ số}{sequence 3 chữ số}
     * VD: LG07001, TS02003
     */
    private function generateBatchCode(int $tenantId, string $categoryCode): string
    {
        $prefix    = self::CATEGORY_PREFIX[$categoryCode] ?? 'KH';
        $entPart   = str_pad($tenantId, 2, '0', STR_PAD_LEFT);
        $pattern   = $prefix . $entPart . '%';

        $last = Batch::where('enterprise_id', $tenantId)
            ->where('code', 'like', $pattern)
            ->orderByDesc('code')
            ->value('code');

        $seq  = $last ? (intval(substr($last, -3)) + 1) : 1;

        return $prefix . $entPart . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $tenantId  = $this->tenantId($request);
        $q         = $request->string('q')->toString();
        $productId = $request->query('product_id');

        $batches = Batch::with('product:id,name,category_id')
            ->where('enterprise_id', $tenantId)
            ->when($q, fn($query) => $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('product_name', 'like', "%{$q}%");
            }))
            ->when($productId, fn($query) => $query->where('product_id', $productId))
            ->withCount('events')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Load category_code để frontend render prefix preview
        $products = Product::with('category:id,code')
            ->where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'gtin', 'category_id'])
            ->map(fn($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'gtin'          => $p->gtin,
                'category_id'   => $p->category_id,
                'category_code' => $p->category?->code,
            ]);

        return Inertia::render('Batches/Index', [
            'batches'  => $batches,
            'products' => $products,
            'filters'  => ['q' => $q, 'product_id' => $productId],
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'description'     => 'nullable|string|max:2000',
            'production_date' => 'nullable|date',
            'expiry_date'     => 'nullable|date|after_or_equal:production_date',
            'quantity'        => 'nullable|integer|min:1',
            'unit'            => 'nullable|string|max:50',
            'certifications'  => 'nullable|array',           // ← thêm
            'certifications.*'=> 'nullable|string|max:100',
        ]);

        // Đảm bảo product thuộc đúng tenant
        $product = Product::with('category:id,code')
            ->where('id', $data['product_id'])
            ->where('enterprise_id', $tenantId)
            ->first();

        if (!$product) {
            return back()->withErrors(['product_id' => 'Sản phẩm không hợp lệ.']);
        }

        // Tự sinh mã lô
        $code = $this->generateBatchCode($tenantId, $product->category?->code ?? 'khac');

        Batch::create([
            'enterprise_id'  => $tenantId,
            'product_id'     => $product->id,
            'code'           => $code,
            'product_name'   => $product->name,
            'description'    => $data['description'] ?? null,
            'production_date'=> $data['production_date'] ?? null,
            'expiry_date'    => $data['expiry_date'] ?? null,
            'quantity'       => $data['quantity'] ?? null,
            'unit'           => $data['unit'] ?? null,
            'certifications'  => $data['certifications'] ?? [], 
        ]);

        return back()->with('success', "Đã tạo lô {$code}.");
    }

    public function update(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        $data = $request->validate([
            'description'    => 'nullable|string|max:2000',
            'production_date'=> 'nullable|date',
            'expiry_date'    => 'nullable|date|after_or_equal:production_date',
            'quantity'       => 'nullable|integer|min:1',
            'unit'           => 'nullable|string|max:50',
            'certifications'  => 'nullable|array',           
            'certifications.*'=> 'nullable|string|max:100',
        ]);

        $batch->update($data);

        return back()->with('success', 'Cập nhật lô thành công.');
    }

    public function destroy(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        if ($batch->events()->where('status', 'published')->exists()) {
            return back()->withErrors(['error' => 'Không thể xóa lô đã có sự kiện published.']);
        }

        $batch->delete();

        return back()->with('success', 'Xóa lô thành công.');
    }
    public function lineage(Request $request, Batch $batch)
{
    $tenantId = $this->tenantId($request);
    abort_unless($batch->enterprise_id === $tenantId, 403);

    // Load đầy đủ relations cho batch hiện tại
    $batch->load([
        'product:id,name,gtin,category_id',
        'product.category:id,name_vi,icon',
        'enterprise:id,name,code',
        'originEnterprise:id,name,code',
        'publishedEvents',
        'childBatches.enterprise:id,name,code',
        'childBatches.publishedEvents',
    ]);

    // Đệ quy build ancestors
    $ancestors = $batch->buildAncestors();

    // Flatten cây thành danh sách nodes + edges cho frontend vẽ sơ đồ
    $nodes = [];
    $edges = [];

    $this->flattenLineage($batch, $ancestors, $nodes, $edges, $tenantId);

    // Load children (lô con được tách ra từ batch này)
    $children = \App\Models\BatchLineage::where('input_batch_id', $batch->id)
        ->where('transformation_type', 'split')
        ->with(['outputBatch.enterprise:id,name,code', 'outputBatch.publishedEvents'])
        ->get();

    foreach ($children as $child) {
        $cb = $child->outputBatch;
        if (!$cb) continue;

        $cbId = 'batch-' . $cb->id;
        $currentId = 'batch-' . $batch->id;

        if (!isset($nodes[$cbId])) {
            $nodes[$cbId] = $this->formatNode($cb, 'split_child');
        }
        $edges[] = [
            'from'     => $currentId,
            'to'       => $cbId,
            'type'     => 'split',
            'label'    => 'Tách → ' . ($child->quantity ?? '') . ' ' . ($child->unit ?? ''),
        ];
    }

    // Merged outputs (batch này là input của merge nào đó)
    $mergedOutputs = \App\Models\BatchLineage::where('input_batch_id', $batch->id)
        ->where('transformation_type', 'merge')
        ->with(['outputBatch.enterprise:id,name,code', 'outputBatch.publishedEvents'])
        ->get();

    foreach ($mergedOutputs as $mo) {
        $ob = $mo->outputBatch;
        if (!$ob) continue;

        $obId = 'batch-' . $ob->id;
        $currentId = 'batch-' . $batch->id;

        if (!isset($nodes[$obId])) {
            $nodes[$obId] = $this->formatNode($ob, 'merge_output');
        }
        $edges[] = [
            'from'  => $currentId,
            'to'    => $obId,
            'type'  => 'merge',
            'label' => 'Gộp → ' . ($mo->quantity ?? '') . ' ' . ($mo->unit ?? ''),
        ];
    }

    return Inertia::render('Batches/Lineage', [
        'batch'   => $this->formatLineageBatch($batch),
        'nodes'   => array_values($nodes),
        'edges'   => $edges,
    ]);
}

    private function flattenLineage(
        \App\Models\Batch $currentBatch,
        array             $ancestors,
        array             &$nodes,
        array             &$edges,
        int               $tenantId
    ): void {
        $currentId = 'batch-' . $currentBatch->id;

        if (!isset($nodes[$currentId])) {
            $isOwn = $currentBatch->enterprise_id === $tenantId;
            $nodes[$currentId] = $this->formatNode($currentBatch, $isOwn ? 'current' : 'external');
        }

        foreach ($ancestors as $ancestor) {
            $rel = $ancestor['relation'];

            if ($rel === 'split_from' && isset($ancestor['batch'])) {
                $parentBatch = $ancestor['batch'];
                $parentId    = 'batch-' . $parentBatch->id;

                if (!isset($nodes[$parentId])) {
                    $nodes[$parentId] = $this->formatNode($parentBatch, 'parent');
                }

                $edges[] = [
                    'from'  => $parentId,
                    'to'    => $currentId,
                    'type'  => 'split',
                    'label' => 'Tách lô',
                ];

                // Đệ quy lên cha của cha
                if (!empty($ancestor['ancestors'])) {
                    $this->flattenLineage($parentBatch, $ancestor['ancestors'], $nodes, $edges, $tenantId);
                }
            }

            elseif ($rel === 'merged_from' && isset($ancestor['batch'])) {
                $inputBatch = $ancestor['batch'];
                $inputId    = 'batch-' . $inputBatch->id;

                if (!isset($nodes[$inputId])) {
                    $nodes[$inputId] = $this->formatNode($inputBatch, 'merge_input');
                }

                $qty = isset($ancestor['quantity']) ? $ancestor['quantity'] . ' ' . ($ancestor['unit'] ?? '') : '';
                $edges[] = [
                    'from'  => $inputId,
                    'to'    => $currentId,
                    'type'  => 'merge',
                    'label' => 'Gộp' . ($qty ? " ({$qty})" : ''),
                ];

                if (!empty($ancestor['ancestors'])) {
                    $this->flattenLineage($inputBatch, $ancestor['ancestors'], $nodes, $edges, $tenantId);
                }
            }

            elseif ($rel === 'received_from' && isset($ancestor['transfer'])) {
                $transfer = $ancestor['transfer'];
                $fromEnt  = $transfer->fromEnterprise;

                // Tạo pseudo-node cho DN gửi
                $fromId = 'enterprise-' . ($fromEnt?->id ?? 'unknown');
                if (!isset($nodes[$fromId])) {
                    $nodes[$fromId] = [
                        'id'      => $fromId,
                        'type'    => 'enterprise',
                        'label'   => $fromEnt?->name ?? 'DN không xác định',
                        'code'    => $fromEnt?->code,
                        'batch'   => null,
                    ];
                }

                $edges[] = [
                    'from'  => $fromId,
                    'to'    => $currentId,
                    'type'  => 'transfer',
                    'label' => 'Chuyển giao · ' . ($transfer->quantity ?? '') . ' ' . ($transfer->unit ?? ''),
                ];
            }
        }
    }

    /**
     * Format 1 batch thành node cho frontend
     */
    private function formatNode(\App\Models\Batch $batch, string $type): array
    {
        return [
            'id'             => 'batch-' . $batch->id,
            'type'           => $type,
            'batch_id'       => $batch->id,
            'code'           => $batch->code,
            'product_name'   => $batch->product?->name ?? $batch->product_name ?? '—',
            'batch_type'     => $batch->batch_type,
            'status'         => $batch->status,
            'enterprise'     => $batch->enterprise?->name,
            'enterprise_code'=> $batch->enterprise?->code,
            'event_count'    => $batch->publishedEvents?->count() ?? 0,
            'quantity'       => $batch->current_quantity ?? $batch->quantity,
            'unit'           => $batch->unit,
        ];
    }

    /**
     * Format batch chính để truyền sang Vue
     */
    private function formatLineageBatch(\App\Models\Batch $batch): array
    {
        return [
            'id'           => $batch->id,
            'code'         => $batch->code,
            'product_name' => $batch->product?->name ?? $batch->product_name,
            'batch_type'   => $batch->batch_type,
            'status'       => $batch->status,
            'quantity'     => $batch->current_quantity ?? $batch->quantity,
            'unit'         => $batch->unit,
            'enterprise'   => $batch->enterprise?->name,
            'category'     => $batch->product?->category ? [
                'name_vi' => $batch->product->category->name_vi,
                'icon'    => $batch->product->category->icon,
            ] : null,
            'completeness' => $batch->completenessScore(),
            'event_count'  => $batch->publishedEvents->count(),
        ];
    }
}