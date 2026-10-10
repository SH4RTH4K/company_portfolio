<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Portfolio;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolios = Portfolio::with(['industry', 'projectCategory'])->orderBy('display_order')->get();

        return view('admin.portfolios.index', compact('portfolios'));
    }

    public function create()
    {
        $industries = Industry::where('is_active', true)->orderBy('display_order')->get();
        $categories = ProjectCategory::orderBy('display_order')->get();

        return view('admin.portfolios.create', compact('industries', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name_en' => 'required|string|max:255',
            'project_name_bn' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'industry_id' => 'nullable|exists:industries,id',
            'project_category_id' => 'nullable|exists:project_categories,id',
            'project_date' => 'nullable|date',
            'project_url' => 'nullable|url',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'challenge_en' => 'nullable|string',
            'challenge_bn' => 'nullable|string',
            'solution_en' => 'nullable|string',
            'solution_bn' => 'nullable|string',
            'results_en' => 'nullable|string',
            'results_bn' => 'nullable|string',
            'technology_used' => 'nullable|string',
            'testimonial_en' => 'nullable|string',
            'testimonial_bn' => 'nullable|string',
            'is_featured' => 'boolean',
            'status' => 'required|in:draft,published',
            'display_order' => 'integer|min:0',
        ]);

        $validated['slug_en'] = Str::slug($validated['project_name_en']);
        if (! empty($validated['project_name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['project_name_bn']);
        }

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        Portfolio::create($validated);

        return redirect()->route('admin.portfolios.index')->with('success', 'Portfolio created successfully.');
    }

    public function edit(Portfolio $portfolio)
    {
        $industries = Industry::where('is_active', true)->orderBy('display_order')->get();
        $categories = ProjectCategory::orderBy('display_order')->get();

        return view('admin.portfolios.edit', compact('portfolio', 'industries', 'categories'));
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        $validated = $request->validate([
            'project_name_en' => 'required|string|max:255',
            'project_name_bn' => 'nullable|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'industry_id' => 'nullable|exists:industries,id',
            'project_category_id' => 'nullable|exists:project_categories,id',
            'project_date' => 'nullable|date',
            'project_url' => 'nullable|url',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'challenge_en' => 'nullable|string',
            'challenge_bn' => 'nullable|string',
            'solution_en' => 'nullable|string',
            'solution_bn' => 'nullable|string',
            'results_en' => 'nullable|string',
            'results_bn' => 'nullable|string',
            'technology_used' => 'nullable|string',
            'testimonial_en' => 'nullable|string',
            'testimonial_bn' => 'nullable|string',
            'is_featured' => 'boolean',
            'status' => 'required|in:draft,published',
            'display_order' => 'integer|min:0',
        ]);

        $validated['slug_en'] = Str::slug($validated['project_name_en']);
        if (! empty($validated['project_name_bn'])) {
            $validated['slug_bn'] = Str::slug($validated['project_name_bn']);
        }

        if ($validated['status'] === 'published' && ! $portfolio->published_at) {
            $validated['published_at'] = now();
        }

        $portfolio->update($validated);

        return redirect()->route('admin.portfolios.index')->with('success', 'Portfolio updated successfully.');
    }

    public function destroy(Portfolio $portfolio)
    {
        $portfolio->delete();

        return redirect()->route('admin.portfolios.index')->with('success', 'Portfolio deleted successfully.');
    }
}
