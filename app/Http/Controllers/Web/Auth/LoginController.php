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
            $message = (string) ($response['message'] ?? 'Sign in failed. Please try again.');
            $messageLower = strtolower($message);

            $popup = [
                'type' => 'error',
                'title' => 'Sign-in failed',
                'message' => $message,
            ];

            if (str_contains($messageLower, 'corp id')) {
                $popup['type'] = 'warning';
                $popup['title'] = 'Corp ID required';
            } elseif (str_contains($messageLower, 'not registered')) {
                $popup['type'] = 'info';
                $popup['title'] = 'Customer not registered';
            } elseif (str_contains($messageLower, 'wrong password')) {
                $popup['type'] = 'error';
                $popup['title'] = 'Wrong password';
            }

            return back()
                ->withErrors(['username_or_email' => $message])
                ->withInput($request->except('password'))
                ->with('auth_popup', $popup);
        }

        $payload = $response['data'] ?? [];

        return $payload['role'] === 'customer'
            ? redirect()->route('customer.dashboard')
            : redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->api->post('/auth/logout');
        return redirect()->route('auth.dotnet.login');
    }
}
