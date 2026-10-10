<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IndustryController extends Controller
{
    public function index()
    {
        $industries = Industry::orderBy('display_order')->get();

        return view('admin.industries.index', compact('industries'));
    }

    public function create()
    {
        return view('admin.industries.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug_en'] = Str::slug($validated['title_en']);
        if (! empty($validated['title_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['title_bn']);
        }

        Industry::create($validated);

        return redirect()->route('admin.industries.index')->with('success', 'Industry created successfully.');
    }

    public function edit(Industry $industry)
    {
        return view('admin.industries.edit', compact('industry'));
    }

    public function update(Request $request, Industry $industry)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug_en'] = Str::slug($validated['title_en']);
        if (! empty($validated['title_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['title_bn']);
        }

        $industry->update($validated);

        return redirect()->route('admin.industries.index')->with('success', 'Industry updated successfully.');
    }

    public function destroy(Industry $industry)
    {
        $industry->delete();

        return redirect()->route('admin.industries.index')->with('success', 'Industry deleted successfully.');
    }
}
