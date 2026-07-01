<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $payload = $this->authService->attempt(
            $request->email,
            $request->password,
            $request->boolean('remember', false)
        );

        if (!$payload) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        return $payload['role'] === 'customer'
            ? redirect()->route('customer.dashboard')
            : redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout();
        return redirect()->route('auth.login');
    }
}
