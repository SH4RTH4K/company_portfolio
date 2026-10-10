<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::orderBy('display_order')->get();

        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return view('admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'designation_en' => 'nullable|string|max:255',
            'designation_bn' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
            'company_logo' => 'nullable|string|max:255',
            'testimonial_en' => 'required|string',
            'testimonial_bn' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        Testimonial::create($validated);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created successfully.');
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'designation_en' => 'nullable|string|max:255',
            'designation_bn' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
            'company_logo' => 'nullable|string|max:255',
            'testimonial_en' => 'required|string',
            'testimonial_bn' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $testimonial->update($validated);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated successfully.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial deleted successfully.');
    }
}
