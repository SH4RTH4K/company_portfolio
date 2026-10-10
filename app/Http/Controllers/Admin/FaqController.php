<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Service;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::with('service')->orderBy('category')->orderBy('display_order')->get();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        $services = Service::where('is_active', true)->orderBy('title_en')->get();
        $categories = ['General', 'Managed IT', 'AMC', 'Products', 'Software', 'Cloud', 'Security'];

        return view('admin.faqs.create', compact('services', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_en' => 'required|string|max:500',
            'question_bn' => 'nullable|string|max:500',
            'answer_en' => 'required|string',
            'answer_bn' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'service_id' => 'nullable|exists:services,id',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        Faq::create($validated);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq)
    {
        $services = Service::where('is_active', true)->orderBy('title_en')->get();
        $categories = ['General', 'Managed IT', 'AMC', 'Products', 'Software', 'Cloud', 'Security'];

        return view('admin.faqs.edit', compact('faq', 'services', 'categories'));
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question_en' => 'required|string|max:500',
            'question_bn' => 'nullable|string|max:500',
            'answer_en' => 'required|string',
            'answer_bn' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'service_id' => 'nullable|exists:services,id',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $faq->update($validated);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }
}
