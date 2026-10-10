<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function index()
    {
        $redirects = Redirect::orderBy('created_at', 'desc')->get();

        return view('admin.redirects.index', compact('redirects'));
    }

    public function create()
    {
        return view('admin.redirects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'old_url' => 'required|string|max:255|unique:redirects,old_url',
            'new_url' => 'required|string|max:255',
            'status_code' => 'required|in:301,302',
            'is_active' => 'boolean',
        ]);

        // Strip leading slash if present to normalize
        $validated['old_url'] = ltrim($validated['old_url'], '/');
        $validated['is_active'] = $request->has('is_active');

        Redirect::create($validated);

        return redirect()->route('admin.redirects.index')->with('success', 'Redirect created successfully.');
    }

    public function edit(Redirect $redirect)
    {
        return view('admin.redirects.edit', compact('redirect'));
    }

    public function update(Request $request, Redirect $redirect)
    {
        $validated = $request->validate([
            'old_url' => 'required|string|max:255|unique:redirects,old_url,'.$redirect->id,
            'new_url' => 'required|string|max:255',
            'status_code' => 'required|in:301,302',
            'is_active' => 'boolean',
        ]);

        $validated['old_url'] = ltrim($validated['old_url'], '/');
        $validated['is_active'] = $request->has('is_active');

        $redirect->update($validated);

        return redirect()->route('admin.redirects.index')->with('success', 'Redirect updated successfully.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return redirect()->route('admin.redirects.index')->with('success', 'Redirect deleted successfully.');
    }
}
