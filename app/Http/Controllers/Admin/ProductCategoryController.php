<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::orderBy('display_order')->get();

        return view('admin.product_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.product_categories.create');
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

        ProductCategory::create($validated);

        return redirect()->route('admin.product-categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(ProductCategory $productCategory)
    {
        return view('admin.product_categories.edit', compact('productCategory'));
    }

    public function update(Request $request, ProductCategory $productCategory)
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

        $productCategory->update($validated);

        return redirect()->route('admin.product-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return redirect()->route('admin.product-categories.index')->with('success', 'Category deleted successfully.');
    }
}
