<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'corp_id'          => 'nullable|string|max:30',
            'username_or_email'=> 'required|string|max:255',
            'password'         => 'required|string',
        ]);

        $response = $this->api->post('/auth/login', [
            'login' => $request->string('username_or_email')->toString(),
            'password' => $request->password,
            'remember' => $request->boolean('remember', false),
            'corp_id' => $request->filled('corp_id') ? $request->string('corp_id')->toString() : null,
        ]);

        if (!($response['success'] ?? false)) {
            return back()->withErrors(['username_or_email' => 'Invalid credentials. Check Corp ID, username/email, and password.'])->withInput();
        }

        $payload = $response['data'] ?? [];

        return $payload['role'] === 'customer'
            ? redirect()->route('customer.dashboard')
            : redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->api->post('/auth/logout');
        return redirect()->route('auth.login');
    }
}
