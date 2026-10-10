<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::orderBy('display_order')->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('admin.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'logo_upload' => [
                'nullable',
                File::image()
                    ->max('1mb')
                    ->dimensions(Rule::dimensions()->maxWidth(1200)->maxHeight(600)),
            ],
            'website' => 'nullable|url|max:255',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo_upload')) {
            $validated['logo'] = $request->file('logo_upload')->store('client-logos', 'public');
        }

        unset($validated['logo_upload']);

        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        Client::create($validated);

        return redirect()->route('admin.clients.index')->with('success', 'Client created successfully.');
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'logo_upload' => [
                'nullable',
                File::image()
                    ->max('1mb')
                    ->dimensions(Rule::dimensions()->maxWidth(1200)->maxHeight(600)),
            ],
            'remove_logo' => 'nullable|boolean',
            'website' => 'nullable|url|max:255',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        unset($validated['logo_upload'], $validated['remove_logo']);

        if ($request->boolean('remove_logo') || $request->hasFile('logo_upload')) {
            $this->deleteClientLogo($client->logo);
            $validated['logo'] = null;
        }

        if ($request->hasFile('logo_upload')) {
            $validated['logo'] = $request->file('logo_upload')->store('client-logos', 'public');
        } elseif ($client->logo !== ($validated['logo'] ?? null)) {
            $this->deleteClientLogo($client->logo);
        }

        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $client->update($validated);

        return redirect()->route('admin.clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->deleteClientLogo($client->logo);

        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Client deleted successfully.');
    }

    private function deleteClientLogo(?string $logo): void
    {
        if ($logo && str($logo)->startsWith('client-logos/')) {
            Storage::disk('public')->delete($logo);
        }
    }
}
