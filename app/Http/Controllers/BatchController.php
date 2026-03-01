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

        $products = Product::where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'gtin', 'category_id']);

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
            'product_id'      => 'nullable|integer|exists:products,id',
            'code'            => 'required|string|max:50',
            'product_name'    => 'nullable|string|max:255',
            'description'     => 'nullable|string|max:2000',
            'production_date' => 'nullable|date',
            'expiry_date'     => 'nullable|date|after_or_equal:production_date',
            'quantity'        => 'nullable|integer|min:1',
            'unit'            => 'nullable|string|max:50',
        ]);

        // Đảm bảo product_id thuộc đúng tenant
        if (!empty($data['product_id'])) {
            $product = Product::where('id', $data['product_id'])
                ->where('enterprise_id', $tenantId)
                ->first();
            if (!$product) {
                return back()->withErrors(['product_id' => 'Sản phẩm không hợp lệ.']);
            }
            // Tự điền product_name từ product nếu không nhập
            if (empty($data['product_name'])) {
                $data['product_name'] = $product->name;
            }
        }

        $exists = Batch::where('enterprise_id', $tenantId)
            ->where('code', $data['code'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Mã lô đã tồn tại trong doanh nghiệp.']);
        }

        Batch::create([
            'enterprise_id' => $tenantId,
            ...$data,
        ]);

        return back()->with('success', 'Tạo lô thành công.');
    }

    public function update(Request $request, Batch $batch)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);

        $data = $request->validate([
            'code'            => 'required|string|max:50',
            'product_name'    => 'nullable|string|max:255',
            'description'     => 'nullable|string|max:2000',
            'production_date' => 'nullable|date',
            'expiry_date'     => 'nullable|date|after_or_equal:production_date',
            'quantity'        => 'nullable|integer|min:1',
            'unit'            => 'nullable|string|max:50',
        ]);

        $exists = Batch::where('enterprise_id', $tenantId)
            ->where('code', $data['code'])
            ->where('id', '!=', $batch->id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Mã lô đã tồn tại trong doanh nghiệp.']);
        }

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