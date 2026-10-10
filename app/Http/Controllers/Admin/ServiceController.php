<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDivision;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('businessDivision')->orderBy('display_order')->get();

        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        $divisions = BusinessDivision::orderBy('display_order')->get();

        return view('admin.services.create', compact('divisions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_division_id' => 'nullable|exists:business_divisions,id',
            'title_en' => 'required|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
            'status' => 'required|in:draft,published',
        ]);

        $validated['slug_en'] = Str::slug($validated['title_en']);
        if (! empty($validated['title_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['title_bn']);
        }

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $service = Service::create($validated);
        $service->syncSeoMeta($request->all());

        return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        $divisions = BusinessDivision::orderBy('display_order')->get();

        return view('admin.services.edit', compact('service', 'divisions'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'business_division_id' => 'nullable|exists:business_divisions,id',
            'title_en' => 'required|string|max:255',
            'title_bn' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
            'status' => 'required|in:draft,published',
        ]);

        $validated['slug_en'] = Str::slug($validated['title_en']);
        if (! empty($validated['title_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['title_bn']);
        } else {
            $validated['slug_bn'] = null;
        }

        if ($validated['status'] === 'published' && ! $service->published_at) {
            $validated['published_at'] = now();
        }

        $service->update($validated);
        $service->syncSeoMeta($request->all());

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Service deleted successfully.');
    }
}
