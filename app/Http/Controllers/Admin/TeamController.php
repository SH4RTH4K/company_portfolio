<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    public function index(): View
    {
        $teams = Team::orderBy('display_order')->get();

        return view('admin.teams.index', compact('teams'));
    }

    public function create(): View
    {
        return view('admin.teams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'designation_en' => 'nullable|string|max:255',
            'designation_bn' => 'nullable|string|max:255',
            'bio_en' => 'nullable|string',
            'bio_bn' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'email' => 'nullable|email|max:255',
            'linkedin' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'website' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|url|max:255',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('team-members', 'public');
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['display_order'] = $validated['display_order'] ?? 0;

        Team::create($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Team member created successfully.');
    }

    public function edit(Team $team): View
    {
        return view('admin.teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'designation_en' => 'nullable|string|max:255',
            'designation_bn' => 'nullable|string|max:255',
            'bio_en' => 'nullable|string',
            'bio_bn' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_photo' => 'nullable|boolean',
            'email' => 'nullable|email|max:255',
            'linkedin' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'website' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|url|max:255',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        unset($validated['remove_photo']);

        if ($request->boolean('remove_photo') || $request->hasFile('photo')) {
            $this->deleteTeamPhoto($team);
            $validated['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('team-members', 'public');
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['display_order'] = $validated['display_order'] ?? 0;

        $team->update($validated);

        return redirect()->route('admin.teams.index')->with('success', 'Team member updated successfully.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->deleteTeamPhoto($team);

        $team->delete();

        return redirect()->route('admin.teams.index')->with('success', 'Team member deleted successfully.');
    }

    private function deleteTeamPhoto(Team $team): void
    {
        if ($team->photo) {
            Storage::disk('public')->delete($team->photo);
        }
    }
}
