<?php

namespace App\Services\Auth;

use App\Models\AuthSessionMap;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Client\Response as HttpResponse;
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
    protected string $apiKey;
    protected bool $verifySsl;
    protected ?string $lastError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('wrkplan.auth.dotnet_api_base_url', ''), '/');
        $this->apiKey = trim((string) config('wrkplan.auth.dotnet_api_key', ''));
        $this->verifySsl = (bool) config('wrkplan.auth.dotnet_verify_ssl', true);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function attempt(string $identifier, string $password, bool $remember = false, ?string $corpId = null): ?array
    {
        $this->lastError = null;

        if ($this->baseUrl === '') {
            Log::error('DotNetAuthProvider: DOTNET_AUTH_API_BASE_URL is not configured.');
            $this->lastError = 'WrkPlan ERP API base URL is not configured.';
            return null;
        }

        $sessionId = (string) Str::uuid();
        $deviceId  = (string) Str::uuid();

        try {
            $response = $this->postWithApiFallback('Security/Login', [
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
                $this->lastError = $this->describeHttpFailure($response->status());
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
                $this->lastError = 'WrkPlan ERP API response is missing access token.';
                Log::warning('DotNet login: no accessToken in response', ['body' => $response->body()]);
                return null;
            }

            // Sync or create a local User record so Laravel auth guards work.
            $user = $this->syncLocalUser($dotnet, $identifier, $corpId);
            if (!$user) {
                $this->lastError = 'Failed to synchronize local user after ERP login.';
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
            $this->lastError = $this->describeException($e->getMessage());
            Log::error('DotNet auth exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build request headers for the .NET API.
     */
    private function buildHeaders(): array
    {
        $headers = [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
        ];

        if ($this->apiKey !== '') {
            $headers['X-API-KEY'] = $this->apiKey;
            $headers['X-Api-Key'] = $this->apiKey;
            $headers['ApiKey'] = $this->apiKey;
        }

        return $headers;
    }

    private function describeHttpFailure(int $status): string
    {
        return match ($status) {
            400, 401 => 'Invalid ERP login credentials.',
            403 => 'ERP API rejected this request. Check API key permissions.',
            404 => 'ERP login endpoint was not found. Verify DOTNET_AUTH_API_BASE_URL.',
            default => 'WrkPlan ERP API error (HTTP ' . $status . '). Please try again.',
        };
    }

    private function describeException(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'could not resolve host')) {
            return 'ERP API host could not be resolved. Check DOTNET_AUTH_API_BASE_URL and DNS.';
        }

        if (str_contains($lower, 'connection refused')) {
            return 'ERP API connection was refused. Ensure the .NET API server is running.';
        }

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return 'ERP API request timed out. Check server/network connectivity.';
        }

        if (str_contains($lower, 'ssl')) {
            return 'ERP API SSL/TLS handshake failed. Verify certificate configuration.';
        }

        return 'ERP API is unreachable at the moment.';
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
            $response = $this->postWithApiFallback('Security/RefreshToken', [
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

    /**
     * Try both base URL variants:
     * - as configured (e.g. https://host կամ https://host/api)
     * - toggled /api suffix variant
     */
    private function postWithApiFallback(string $path, array $payload): HttpResponse
    {
        $client = Http::withOptions(['verify' => $this->verifySsl])
            ->withHeaders($this->buildHeaders())
            ->timeout(15);

        $urls = $this->buildCandidateUrls($path);
        $response = $client->post($urls[0], $payload);

        if ($response->status() !== 404 || count($urls) < 2) {
            return $response;
        }

        return $client->post($urls[1], $payload);
    }

    private function buildCandidateUrls(string $path): array
    {
        $base = rtrim($this->baseUrl, '/');
        $path = ltrim($path, '/');

        $urls = [$base . '/' . $path];

        if (str_ends_with(strtolower($base), '/api')) {
            $urls[] = substr($base, 0, -4) . '/' . $path;
        } else {
            $urls[] = $base . '/api/' . $path;
        }

        return array_values(array_unique($urls));
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
