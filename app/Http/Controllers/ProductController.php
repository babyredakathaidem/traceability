<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $q        = $request->string('q')->toString();

        $products = Product::with('category:id,code,name_vi,icon')
            ->where('enterprise_id', $tenantId)
            ->when($q, fn($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('gtin', 'like', "%{$q}%");
            }))
            ->withCount('batches')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = ProductCategory::orderBy('sort_order')->get(['id', 'code', 'name_vi', 'icon']);

        return Inertia::render('Products/Index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => ['q' => $q],
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|integer|exists:product_categories,id',
            'gtin'        => ['nullable', 'string', 'max:14', 'regex:/^\d{8,14}$/'],
            'description' => 'nullable|string|max:2000',
            'unit'        => 'nullable|string|max:50',
            'status'      => 'required|in:active,inactive',
            'image'       => 'nullable|file|image|max:5120',
        ]);

        if (!empty($data['gtin'])) {
            $exists = Product::where('enterprise_id', $tenantId)
                ->where('gtin', $data['gtin'])
                ->exists();
            if ($exists) {
                return back()->withErrors(['gtin' => 'GTIN này đã tồn tại trong doanh nghiệp.']);
            }
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("products/{$tenantId}", 'public');
        }

        Product::create([
            'enterprise_id' => $tenantId,
            'category_id'   => $data['category_id'],
            'name'          => $data['name'],
            'gtin'          => $data['gtin'] ?? null,
            'description'   => $data['description'] ?? null,
            'unit'          => $data['unit'] ?? null,
            'status'        => $data['status'],
            'image_path'    => $imagePath,
        ]);

        return back()->with('success', 'Đã thêm sản phẩm.');
    }

    public function update(Request $request, Product $product)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($product->enterprise_id === $tenantId, 403);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|integer|exists:product_categories,id',
            'gtin'        => ['nullable', 'string', 'max:14', 'regex:/^\d{8,14}$/'],
            'description' => 'nullable|string|max:2000',
            'unit'        => 'nullable|string|max:50',
            'status'      => 'required|in:active,inactive',
            'image'       => 'nullable|file|image|max:5120',
        ]);

        if (!empty($data['gtin'])) {
            $exists = Product::where('enterprise_id', $tenantId)
                ->where('gtin', $data['gtin'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($exists) {
                return back()->withErrors(['gtin' => 'GTIN này đã tồn tại trong doanh nghiệp.']);
            }
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store("products/{$tenantId}", 'public');
        }

        $product->update($data);

        return back()->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Request $request, Product $product)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($product->enterprise_id === $tenantId, 403);

        if ($product->batches()->exists()) {
            return back()->withErrors(['error' => 'Không thể xóa sản phẩm đang có lô hàng liên kết.']);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('success', 'Đã xóa sản phẩm.');
    }
}