<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand'])->orderBy('display_order')->get();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('display_order')->get();
        $brands = ProductBrand::orderBy('display_order')->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name_en' => 'required|string|max:255',
            'product_name_bn' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'brand_id' => 'nullable|exists:product_brands,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'show_price' => 'boolean',
            'availability' => 'nullable|string',
            'request_quote' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $validated['slug_en'] = Str::slug($validated['product_name_en']);
        if (! empty($validated['product_name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['product_name_bn']);
        }

        $product = Product::create($validated);
        $product->syncSeoMeta($request->all());

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::orderBy('display_order')->get();
        $brands = ProductBrand::orderBy('display_order')->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_name_en' => 'required|string|max:255',
            'product_name_bn' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'brand_id' => 'nullable|exists:product_brands,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'show_price' => 'boolean',
            'availability' => 'nullable|string',
            'request_quote' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $validated['slug_en'] = Str::slug($validated['product_name_en']);
        if (! empty($validated['product_name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['product_name_bn']);
        } else {
            $validated['slug_bn'] = null;
        }

        $product->update($validated);
        $product->syncSeoMeta($request->all());

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
