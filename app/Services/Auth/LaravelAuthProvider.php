<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\AuthSessionMap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Laravel Auth Provider
 *
 * Current implementation using Laravel's session-based auth.
 * Produces standardized session payload compatible with future .NET provider.
 *
 * PASSWORD MODE (controlled by AUTH_PLAIN_TEXT_PASSWORDS env):
 * - false (production): Uses bcrypt/argon hashed passwords (default)
 * - true (migration mode, non-production): Accepts plain text passwords
 *   This is a TEMPORARY mode for .NET auth migration compatibility only.
 *   Remove/disable before production deployment.
 */
class LaravelAuthProvider implements AuthProviderInterface
{
    protected bool $plainTextMode;

    public function __construct()
    {
        $this->plainTextMode = (bool) config('wrkplan.auth.plain_text_passwords', false);
    }

    public function attempt(string $identifier, string $password, bool $remember = false, ?string $corpId = null): ?array
    {
        $userQuery = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier);
            });

        if (!empty($corpId)) {
            $userQuery->whereHas('tenant', function ($query) use ($corpId) {
                $query->where('corp_id', $corpId);
            });
        }

        $user = $userQuery->first();

        if (!$user) {
            return null;
        }

        $authenticated = $this->verifyPassword($user, $password);

        if (!$authenticated) {
            return null;
        }

        Auth::login($user, $remember);

        $sessionToken = Str::random(64);
        $expiresAt = now()->addMinutes(config('session.lifetime', 120))->toISOString();

        // Store in auth_session_map for provider-agnostic session tracking
        AuthSessionMap::create([
            'user_id'       => $user->id,
            'session_token' => $sessionToken,
            'provider'      => 'laravel',
            'payload'       => $user->toSessionPayload($sessionToken, $expiresAt),
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'expires_at'    => now()->addMinutes(config('session.lifetime', 120)),
        ]);

        return $user->toSessionPayload($sessionToken, $expiresAt);
    }

    protected function verifyPassword(User $user, string $password): bool
    {
        // PLAIN TEXT MODE - Non-production only! For .NET migration compatibility.
        if ($this->plainTextMode) {
            return $user->plain_password === $password || Hash::check($password, $user->password);
        }

        return Hash::check($password, $user->password);
    }

    public function logout(): void
    {
        $token = trim((string) request()->attributes->get('api_session_token', request()->bearerToken() ?: request()->header('X-Session-Token')));

        if ($token !== '') {
            AuthSessionMap::where('session_token', $token)
                ->where('provider', 'laravel')
                ->delete();
        } elseif ($user = Auth::user()) {
            AuthSessionMap::where('user_id', $user->id)
                ->where('provider', 'laravel')
                ->latest()
                ->first()?->delete();
        }

        if (Auth::check()) {
            Auth::logout();
        }

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    public function currentUserPayload(): ?array
    {
        $payload = request()->attributes->get('api_auth_payload');
        if (is_array($payload)) {
            return $payload;
        }

        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return $user->toSessionPayload();
    }

    public function check(): bool
    {
        return Auth::check();
    }

    public function validateToken(string $token): ?array
    {
        $session = AuthSessionMap::where('session_token', $token)
            ->where('provider', 'laravel')
            ->where('expires_at', '>', now())
            ->first();

        return $session?->payload;
    }
}
