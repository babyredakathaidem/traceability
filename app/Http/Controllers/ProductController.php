<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'name'                 => 'required|string|max:255',
            'category_id'          => 'required',
            'custom_category_name' => 'nullable|string|max:100',
            'gtin'                 => ['nullable', 'string', 'max:14', 'regex:/^\d{8,14}$/'],
            'description'          => 'nullable|string|max:2000',
            'unit'                 => 'nullable|string|max:50',
            'status'               => 'required|in:active,inactive',
            'image'                => 'nullable|file|image|max:5120',
        ]);

        // Xử lý danh mục tùy chỉnh
        $categoryId = $this->resolveCategoryId(
            $data['category_id'],
            $data['custom_category_name'] ?? null
        );

        if (!$categoryId) {
            return back()->withErrors(['category_id' => 'Danh mục không hợp lệ.']);
        }

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
            'category_id'   => $categoryId,
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
        abort_unless($product->enterprise_id === $this->tenantId($request), 403);

        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'category_id'          => 'required',
            'custom_category_name' => 'nullable|string|max:100',
            'gtin'                 => ['nullable', 'string', 'max:14', 'regex:/^\d{8,14}$/'],
            'description'          => 'nullable|string|max:2000',
            'unit'                 => 'nullable|string|max:50',
            'status'               => 'required|in:active,inactive',
            'image'                => 'nullable|file|image|max:5120',
        ]);

        $categoryId = $this->resolveCategoryId(
            $data['category_id'],
            $data['custom_category_name'] ?? null
        );

        if (!$categoryId) {
            return back()->withErrors(['category_id' => 'Danh mục không hợp lệ.']);
        }

        if (!empty($data['gtin'])) {
            $exists = Product::where('enterprise_id', $product->enterprise_id)
                ->where('gtin', $data['gtin'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($exists) {
                return back()->withErrors(['gtin' => 'GTIN này đã tồn tại trong doanh nghiệp.']);
            }
        }

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store("products/{$product->enterprise_id}", 'public');
        }

        $product->update([
            'category_id' => $categoryId,
            'name'        => $data['name'],
            'gtin'        => $data['gtin'] ?? null,
            'description' => $data['description'] ?? null,
            'unit'        => $data['unit'] ?? null,
            'status'      => $data['status'],
            'image_path'  => $imagePath,
        ]);

        return back()->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Request $request, Product $product)
    {
        abort_unless($product->enterprise_id === $this->tenantId($request), 403);

        if ($product->batches()->exists()) {
            return back()->withErrors(['product' => 'Không thể xóa sản phẩm đã có lô hàng.']);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
        return back()->with('success', 'Đã xóa sản phẩm.');
    }

    public function show(Request $request, Product $product)
    {
        $tenantId = $this->tenantId($request);
        abort_unless($product->enterprise_id === $tenantId, 403);
        $product->load([
            'category',
            'batches' => fn($q) => $q->withCount('events')->latest(),
        ]);
        return Inertia::render('Products/Show', ['product' => $product]);
    }

    // ── Private helpers ───────────────────────────────────

    /**
     * Xử lý category_id:
     * - Nếu là số và tồn tại trong DB: dùng luôn
     * - Nếu có custom_category_name: tạo danh mục mới, trả về id mới
     */
    private function resolveCategoryId(mixed $categoryId, ?string $customName): ?int
    {
        // Nếu có tên tùy chỉnh → tạo danh mục mới
        if (!empty(trim((string) $customName))) {
            $name = trim($customName);
            $code = 'custom_' . Str::slug($name, '_') . '_' . substr(md5($name . time()), 0, 6);

            $newCategory = ProductCategory::create([
                'code'       => $code,
                'name_vi'    => $name,
                'icon'       => null,
                'tcvn_ref'   => null,
                'sort_order' => 99,
            ]);

            return $newCategory->id;
        }

        // Dùng category_id truyền lên
        $id = (int) $categoryId;
        if ($id > 0 && ProductCategory::where('id', $id)->exists()) {
            return $id;
        }

        return null;
    }
}