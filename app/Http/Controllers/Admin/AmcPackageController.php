<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AmcPackage;
use Illuminate\Http\Request;

class AmcPackageController extends Controller
{
    public function index()
    {
        $packages = AmcPackage::orderBy('display_order')->get();

        return view('admin.amc_packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.amc_packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'price_text_en' => 'nullable|string',
            'price_text_bn' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'is_recommended' => 'boolean',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        AmcPackage::create($validated);

        return redirect()->route('admin.amc-packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(AmcPackage $amcPackage)
    {
        return view('admin.amc_packages.edit', compact('amcPackage'));
    }

    public function update(Request $request, AmcPackage $amcPackage)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'price_text_en' => 'nullable|string',
            'price_text_bn' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'is_recommended' => 'boolean',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $amcPackage->update($validated);

        return redirect()->route('admin.amc-packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(AmcPackage $amcPackage)
    {
        $amcPackage->delete();

        return redirect()->route('admin.amc-packages.index')->with('success', 'Package deleted successfully.');
    }
}
