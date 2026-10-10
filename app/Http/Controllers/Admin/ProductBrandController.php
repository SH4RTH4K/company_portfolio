<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductBrandController extends Controller
{
    public function index()
    {
        $brands = ProductBrand::orderBy('display_order')->get();

        return view('admin.product_brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.product_brands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug_en'] = Str::slug($validated['name_en']);
        if (! empty($validated['name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['name_bn']);
        }

        ProductBrand::create($validated);

        return redirect()->route('admin.product-brands.index')->with('success', 'Brand created successfully.');
    }

    public function edit(ProductBrand $productBrand)
    {
        return view('admin.product_brands.edit', compact('productBrand'));
    }

    public function update(Request $request, ProductBrand $productBrand)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug_en'] = Str::slug($validated['name_en']);
        if (! empty($validated['name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['name_bn']);
        }

        $productBrand->update($validated);

        return redirect()->route('admin.product-brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(ProductBrand $productBrand)
    {
        $productBrand->delete();

        return redirect()->route('admin.product-brands.index')->with('success', 'Brand deleted successfully.');
    }
}
