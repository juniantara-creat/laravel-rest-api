<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:100'],
            'category' => ['sometimes', 'string', 'max:100'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'sort' => ['sometimes', 'in:name,price,stock,created_at'],
            'direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $request->user()->products()
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price', '<=', $filters['max_price']))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }
        unset($data['photo']);

        $product = $request->user()->products()->create($data);
        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $product)
    {
        $record = Product::findOrFail($product);
        $this->authorize('view', $record);
        return new ProductResource($record);
    }

    public function update(UpdateProductRequest $request, int $product)
    {
        $record = Product::findOrFail($product);
        $this->authorize('update', $record);
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($record->photo_path) Storage::disk('public')->delete($record->photo_path);
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }
        unset($data['photo']);
        $record->update($data);

        return new ProductResource($record->refresh());
    }

    public function destroy(Request $request, int $product)
    {
        $record = Product::findOrFail($product);
        $this->authorize('delete', $record);
        if ($record->photo_path) Storage::disk('public')->delete($record->photo_path);
        $record->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
