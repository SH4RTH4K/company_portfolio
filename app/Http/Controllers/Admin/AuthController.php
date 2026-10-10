<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->merge([
            'username' => str($request->input('username', ''))->trim()->lower()->toString(),
        ]);

        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            AuditLog::log('login', 'Auth', Auth::user());

            return redirect()->intended('admin/dashboard');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            AuditLog::log('logout', 'Auth', Auth::user());
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
