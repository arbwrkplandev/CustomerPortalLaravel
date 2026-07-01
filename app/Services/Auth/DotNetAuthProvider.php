<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DotNet Auth Provider (STUB - Not yet activated)
 *
 * HOW TO ACTIVATE .NET AUTH:
 * =====================================================
 * 1. Set AUTH_PROVIDER=dotnet in .env
 * 2. Set DOTNET_AUTH_API_BASE_URL=https://your-dotnet-auth-api.com
 * 3. Set DOTNET_AUTH_API_KEY=your-api-key
 * 4. Implement the actual API calls below
 * 5. Ensure your .NET API returns the standardized session payload:
 *    {
 *      "user_id": int,
 *      "tenant_id": int,
 *      "role": string,
 *      "display_name": string,
 *      "email": string,
 *      "session_token": string,
 *      "expires_at": string (ISO 8601),
 *      "permissions": string[]
 *    }
 * 6. No frontend changes required - the UI consumes the payload via AuthService.
 * =====================================================
 *
 * DOTNET AUTH API ENDPOINTS EXPECTED:
 * POST /auth/login         -> { email, password } -> session payload
 * POST /auth/logout        -> { session_token }
 * GET  /auth/me            -> session payload
 * GET  /auth/validate      -> { token } -> session payload or 401
 */
class DotNetAuthProvider implements AuthProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('wrkplan.auth.dotnet_api_base_url', '');
        $this->apiKey  = config('wrkplan.auth.dotnet_api_key', '');
    }

    public function attempt(string $email, string $password, bool $remember = false): ?array
    {
        // TODO: Implement when .NET auth API is ready
        try {
            $response = Http::withHeaders([
                'X-API-Key'    => $this->apiKey,
                'Accept'       => 'application/json',
            ])->post($this->baseUrl . '/auth/login', [
                'email'    => $email,
                'password' => $password,
                'remember' => $remember,
            ]);

            if ($response->successful()) {
                $payload = $response->json();
                // Store in session for subsequent requests
                session(['auth_payload' => $payload]);
                return $payload;
            }

            Log::warning('DotNet auth failed', ['status' => $response->status()]);
            return null;
        } catch (\Exception $e) {
            Log::error('DotNet auth error: ' . $e->getMessage());
            return null;
        }
    }

    public function logout(): void
    {
        $payload = session('auth_payload');
        if ($payload && isset($payload['session_token'])) {
            try {
                Http::withHeaders(['X-API-Key' => $this->apiKey])
                    ->post($this->baseUrl . '/auth/logout', [
                        'session_token' => $payload['session_token'],
                    ]);
            } catch (\Exception $e) {
                Log::error('DotNet logout error: ' . $e->getMessage());
            }
        }
        session()->forget('auth_payload');
        session()->invalidate();
        session()->regenerateToken();
    }

    public function currentUserPayload(): ?array
    {
        return session('auth_payload');
    }

    public function check(): bool
    {
        return session()->has('auth_payload');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->get($this->baseUrl . '/auth/validate', ['token' => $token]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('DotNet token validation error: ' . $e->getMessage());
            return null;
        }
    }
}
