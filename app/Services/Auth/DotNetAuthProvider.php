<?php

namespace App\Services\Auth;

use App\Models\AuthSessionMap;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * DotNet Auth Provider
 *
 * Authenticates against the WrkPlan ERP .NET API.
 * After successful .NET login, it syncs a local User record and calls Auth::login()
 * so existing Laravel middleware (auth, admin.only, tenant.scope) continue to work.
 *
 * Token lifecycle:
 *   - Access/refresh tokens are stored in the session under dotnet_* keys.
 *   - The DotNetRefreshToken middleware auto-refreshes tokens before expiry.
 */
class DotNetAuthProvider implements AuthProviderInterface
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('wrkplan.auth.dotnet_api_base_url', ''), '/');
    }

    public function attempt(string $identifier, string $password, bool $remember = false, ?string $corpId = null): ?array
    {
        if ($this->baseUrl === '') {
            Log::error('DotNetAuthProvider: DOTNET_AUTH_API_BASE_URL is not configured.');
            return null;
        }

        $sessionId = (string) Str::uuid();
        $deviceId  = (string) Str::uuid();

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($this->baseUrl . '/Security/Login', [
                'corpID'         => (string) ($corpId ?? ''),
                'loginID'        => $identifier,
                'password'       => $password,
                'encryptedData'  => '',
                'nonce'          => '',
                'tag'            => '',
                'key'            => '',
                'sessionId'      => $sessionId,
                'deviceType'     => 'Web',
                'userAgent'      => (string) (request()->userAgent() ?? 'Laravel/Portal'),
                'deviceId'       => $deviceId,
                'idCompany'      => (int) ($corpId ?? 0),
                'keepMeSignedIn' => $remember,
            ]);

            if (!$response->successful()) {
                Log::warning('DotNet login failed', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'loginID'  => $identifier,
                ]);
                return null;
            }

            $dotnet = $response->json();

            // A valid .NET login response must contain an access token.
            if (empty($dotnet['accessToken'])) {
                Log::warning('DotNet login: no accessToken in response', ['body' => $response->body()]);
                return null;
            }

            // Sync or create a local User record so Laravel auth guards work.
            $user = $this->syncLocalUser($dotnet, $identifier, $corpId);
            if (!$user) {
                Log::error('DotNet login: failed to sync local user');
                return null;
            }

            Auth::login($user, $remember);

            // Store .NET tokens in session for the refresh middleware.
            // The API returns PascalCase keys (UserID, IDCompany, SessionId, etc.)
            $expiresIn = (int) ($dotnet['ExpiresIn'] ?? 3600);
            $expiresAt = now()->addSeconds($expiresIn)->toISOString();

            session([
                'dotnet_access_token'      => $dotnet['accessToken'],
                'dotnet_refresh_token'     => $dotnet['refreshToken'] ?? '',
                'dotnet_token_expires_at'  => now()->addSeconds($expiresIn)->timestamp,
                'dotnet_session_id'        => $dotnet['SessionId'] ?? $sessionId,
                'dotnet_user_id'           => (int) ($dotnet['UserID'] ?? 0),
                'dotnet_id_company'        => (int) ($dotnet['IDCompany'] ?? 0),
                'dotnet_token_type'        => $dotnet['TokenType'] ?? 'Bearer',
                'dotnet_expires_in'        => $expiresIn,
                'dotnet_device_type'       => 'Web',
                'dotnet_user_agent'        => (string) (request()->userAgent() ?? 'Laravel/Portal'),
                'dotnet_keep_signed_in'    => $remember,
                'dotnet_user_email'        => $dotnet['UserEmail'] ?? '',
                'dotnet_user_display_name' => $dotnet['UserDisplayName'] ?? '',
                'dotnet_user_group_id'     => (int) ($dotnet['UserGroupId'] ?? 0),
                'dotnet_user_group_name'   => $dotnet['UserGroupName'] ?? '',
            ]);

            $payload = $user->toSessionPayload($dotnet['accessToken'], $expiresAt);
            session(['auth_payload' => $payload]);

            return $payload;

        } catch (\Exception $e) {
            Log::error('DotNet auth exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh the DotNet access token using the stored refresh token.
     * Updates session on success. Returns the new access token or null on failure.
     */
    public function refreshToken(): ?string
    {
        $accessToken  = session('dotnet_access_token', '');
        $refreshToken = session('dotnet_refresh_token', '');

        if (!$accessToken || !$refreshToken) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($this->baseUrl . '/Security/RefreshToken', [
                'accessToken'     => $accessToken,
                'refreshToken'    => $refreshToken,
                'userID'          => session('dotnet_user_id', 0),
                'idCompany'       => session('dotnet_id_company', 0),
                'sessionId'       => session('dotnet_session_id', (string) Str::uuid()),
                'tokenType'       => session('dotnet_token_type', 'Bearer'),
                'expiresIn'       => session('dotnet_expires_in', 3600),
                'deviceType'      => session('dotnet_device_type', 'Web'),
                'userAgent'       => session('dotnet_user_agent', request()->userAgent() ?? 'Laravel/Portal'),
                'keepMeSignedIn'  => session('dotnet_keep_signed_in', false),
                'userEmail'       => session('dotnet_user_email', ''),
                'userDisplayName' => session('dotnet_user_display_name', ''),
                'userGroupId'     => session('dotnet_user_group_id', 0),
                'userGroupName'   => session('dotnet_user_group_name', ''),
            ]);

            if (!$response->successful()) {
                Log::warning('DotNet token refresh failed', ['status' => $response->status()]);
                return null;
            }

            $dotnet = $response->json();

            if (empty($dotnet['accessToken'])) {
                return null;
            }

            // Refresh response also uses PascalCase keys.
            $expiresIn = (int) ($dotnet['ExpiresIn'] ?? session('dotnet_expires_in', 3600));

            session([
                'dotnet_access_token'     => $dotnet['accessToken'],
                'dotnet_refresh_token'    => $dotnet['refreshToken'] ?? $refreshToken,
                'dotnet_token_expires_at' => now()->addSeconds($expiresIn)->timestamp,
                'dotnet_expires_in'       => $expiresIn,
            ]);

            // Keep auth_payload in sync with the new token.
            $existing = session('auth_payload', []);
            if (!empty($existing)) {
                $existing['session_token'] = $dotnet['accessToken'];
                $existing['expires_at']    = now()->addSeconds($expiresIn)->toISOString();
                session(['auth_payload' => $existing]);
            }

            return $dotnet['accessToken'];

        } catch (\Exception $e) {
            Log::error('DotNet token refresh exception: ' . $e->getMessage());
            return null;
        }
    }

    public function logout(): void
    {
        session()->forget([
            'auth_payload',
            'dotnet_access_token',
            'dotnet_refresh_token',
            'dotnet_token_expires_at',
            'dotnet_session_id',
            'dotnet_user_id',
            'dotnet_id_company',
            'dotnet_token_type',
            'dotnet_expires_in',
            'dotnet_device_type',
            'dotnet_user_agent',
            'dotnet_keep_signed_in',
            'dotnet_user_email',
            'dotnet_user_display_name',
            'dotnet_user_group_id',
            'dotnet_user_group_name',
        ]);
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    public function currentUserPayload(): ?array
    {
        return session('auth_payload');
    }

    public function check(): bool
    {
        return Auth::check();
    }

    public function validateToken(string $token): ?array
    {
        // For DotNet sessions the access token IS the session token.
        // Find the matching session payload stored by the middleware.
        $payload = session('auth_payload');
        if ($payload && isset($payload['session_token']) && $payload['session_token'] === $token) {
            return $payload;
        }
        return null;
    }

    // ──────────────────────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Find or create a local User record to satisfy Laravel's auth guards.
     */
    private function syncLocalUser(array $dotnet, string $identifier, ?string $corpId): ?User
    {
        // API returns PascalCase keys: UserEmail, UserDisplayName, UserGroupName, UserID, IDCompany
        $email        = trim((string) ($dotnet['UserEmail'] ?? ''));
        $displayName  = trim((string) ($dotnet['UserDisplayName'] ?? $identifier));
        $groupName    = trim((string) ($dotnet['UserGroupName'] ?? ''));
        $idCompany    = (int) ($dotnet['IDCompany'] ?? 0);
        $dotnetUserId = (int) ($dotnet['UserID'] ?? 0);

        // Fall back to loginID if display name is empty (UserDisplayName can be null)
        if ($displayName === '') {
            $displayName = $identifier;
        }

        $role = $this->resolveRole($groupName, $idCompany);

        $tenantId = null;
        if ($role === 'customer' && $idCompany > 0) {
            $tenant = Tenant::where('id', $idCompany)
                ->orWhere('corp_id', strtoupper((string) $corpId))
                ->first();

            if (!$tenant) {
                // Create a stub tenant so TenantScope middleware is satisfied.
                $tenant = Tenant::create([
                    'name'      => $displayName . ' Company',
                    'corp_id'   => strtoupper($corpId ?: ('DOTNET-' . $idCompany)),
                    'is_active' => true,
                ]);
            }
            $tenantId = $tenant->id;
        }

        // Try to find user by email, or by dotnet userID stored in our DB (via username).
        $user = null;
        if ($email !== '') {
            $user = User::where('email', $email)->first();
        }
        if (!$user && $dotnetUserId > 0) {
            $user = User::where('username', 'dotnet_uid_' . $dotnetUserId)->first();
        }

        if (!$user) {
            $user = new User();
            $user->email    = $email ?: ('dotnet_uid_' . $dotnetUserId . '@wrkplan.local');
            $user->username = $email !== '' ? $identifier : 'dotnet_uid_' . $dotnetUserId;
            $user->password = Hash::make(Str::random(40));
        }

        $user->name      = $displayName;
        $user->role      = $role;
        $user->tenant_id = $tenantId;
        $user->is_active = true;
        $user->save();

        return $user;
    }

    /**
     * Map a .NET userGroupName to our local role.
     */
    private function resolveRole(string $groupName, int $idCompany): string
    {
        $lower = strtolower($groupName);
        if (str_contains($lower, 'admin') || str_contains($lower, 'super') || str_contains($lower, 'manager')) {
            return 'admin';
        }
        // If no company context, treat as admin.
        if ($idCompany === 0) {
            return 'admin';
        }
        return 'customer';
    }
}
