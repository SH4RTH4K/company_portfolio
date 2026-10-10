<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDivision;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessDivisionController extends Controller
{
    public function index()
    {
        $divisions = BusinessDivision::orderBy('display_order')->get();

        return view('admin.business_divisions.index', compact('divisions'));
    }

    public function create()
    {
        return view('admin.business_divisions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug_en'] = Str::slug($validated['name_en']);
        if (! empty($validated['name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['name_bn']);
        }

        BusinessDivision::create($validated);

        return redirect()->route('admin.business-divisions.index')->with('success', 'Business Division created successfully.');
    }

    public function edit(BusinessDivision $businessDivision)
    {
        return view('admin.business_divisions.edit', compact('businessDivision'));
    }

    public function update(Request $request, BusinessDivision $businessDivision)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug_en'] = Str::slug($validated['name_en']);
        if (! empty($validated['name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['name_bn']);
        } else {
            $validated['slug_bn'] = null;
        }

        $businessDivision->update($validated);

        return redirect()->route('admin.business-divisions.index')->with('success', 'Business Division updated successfully.');
    }

    public function destroy(BusinessDivision $businessDivision)
    {
        $businessDivision->delete();

        return redirect()->route('admin.business-divisions.index')->with('success', 'Business Division deleted successfully.');
    }
}
