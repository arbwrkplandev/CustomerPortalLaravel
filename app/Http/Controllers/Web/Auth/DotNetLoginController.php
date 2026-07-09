<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\DotNetAuthProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles login via the WrkPlan ERP .NET API.
 * Parallel to the existing Laravel login — no existing routes are modified.
 */
class DotNetLoginController extends Controller
{
    public function __construct(protected DotNetAuthProvider $provider) {}

    public function showLogin(): View
    {
        return view('auth.dotnet-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'corp_id'  => 'nullable|string|max:50',
            'login_id' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $corpId     = $request->filled('corp_id') ? trim($request->string('corp_id')->toString()) : null;
        $loginId    = trim($request->string('login_id')->toString());
        $password   = $request->input('password');
        $remember   = $request->boolean('remember', false);

        $payload = $this->provider->attempt($loginId, $password, $remember, $corpId);

        if (!$payload) {
            $message = $this->provider->getLastError()
                ?: 'Invalid credentials or the WrkPlan ERP API is unreachable. Please try again.';

            return back()
                ->withInput($request->except('password'))
                ->with('auth_popup', [
                    'type'    => 'error',
                    'title'   => 'Sign-in failed',
                    'message' => $message,
                ]);
        }

        $user = Auth::user();

        return $user && $user->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('customer.dashboard');
    }
}
