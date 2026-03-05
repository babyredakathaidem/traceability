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
}