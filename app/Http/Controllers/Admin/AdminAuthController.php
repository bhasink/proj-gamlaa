<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('admin.authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'max:160'],
            'password' => ['required', 'string', 'max:160'],
        ]);

        $expectedEmail = (string) config('admin.email');
        $passwordHash  = config('admin.password_hash');

        $emailOk = hash_equals($expectedEmail, $credentials['email']);
        $passwordOk = $passwordHash
            ? Hash::check($credentials['password'], $passwordHash)
            : hash_equals((string) config('admin.password'), $credentials['password']);

        if (! $emailOk || ! $passwordOk) {
            return back()
                ->withInput(['email' => $credentials['email']])
                ->withErrors(['email' => 'Those credentials do not match.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin.authenticated', true);
        $request->session()->put('admin.email', $expectedEmail);

        $intended = $request->session()->pull('intended');
        return redirect($intended ?: route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['admin.authenticated', 'admin.email']);
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.show');
    }
}
