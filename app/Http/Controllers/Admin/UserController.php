<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roleDefinition')->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::options(activeOnly: false);

        return view('admin.users.index', compact('roles', 'users'));
    }

    public function create(): View
    {
        $roles = Role::options();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'username' => $this->normalizedUsername($request->input('username', '')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(array_keys(Role::options()))],
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($response = $this->ensureCanAssignSuperAdmin($request, $validated['role'])) {
            return $response;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active');

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'User account created successfully.');
    }

    public function edit(User $user): View
    {
        $roles = Role::options();

        if ($user->role && ! array_key_exists($user->role, $roles)) {
            $roles[$user->role] = $user->roleDefinition?->name ?? str($user->role)->replace('_', ' ')->title()->toString();
        }

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge([
            'username' => $this->normalizedUsername($request->input('username', '')),
        ]);

        $roleOptions = Role::options();

        if ($user->role && ! array_key_exists($user->role, $roleOptions)) {
            $roleOptions[$user->role] = $user->role;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user)],
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => ['required', Rule::in(array_keys($roleOptions))],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($response = $this->ensureCanAssignSuperAdmin($request, $validated['role'])) {
            return $response;
        }

        if ($response = $this->ensureLastActiveSuperAdminIsPreserved($request, $user, $validated['role'])) {
            return $response;
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active');

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User account updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($this->isLastActiveSuperAdmin($user)) {
            return redirect()->back()->with('error', 'You cannot delete the last remaining active super administrator.');
        }

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User account deleted successfully.');
    }

    private function normalizedUsername(mixed $value): string
    {
        return str((string) $value)->trim()->lower()->toString();
    }

    private function ensureCanAssignSuperAdmin(Request $request, string $role): ?RedirectResponse
    {
        if ($role !== 'super_admin' || $request->user()?->isSuperAdmin()) {
            return null;
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Only a super administrator may assign the super administrator role.');
    }

    private function ensureLastActiveSuperAdminIsPreserved(Request $request, User $user, string $role): ?RedirectResponse
    {
        if (! $this->isLastActiveSuperAdmin($user)) {
            return null;
        }

        if (! $request->has('is_active')) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'You cannot deactivate the last remaining active super administrator.');
        }

        if ($role !== 'super_admin') {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'You cannot demote the last remaining active super administrator.');
        }

        return null;
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        return $user->role === 'super_admin'
            && $user->is_active === true
            && User::where('role', 'super_admin')->where('is_active', true)->count() <= 1;
    }
}
