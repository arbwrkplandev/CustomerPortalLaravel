<?php

namespace App\Http\Middleware;

use App\Services\Auth\DotNetAuthProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Auto-refresh the WrkPlan ERP .NET access token before it expires.
 *
 * Runs on every web request. Only acts when a DotNet session is active
 * (dotnet_access_token present in session). Refreshes if the token will
 * expire within REFRESH_BUFFER_SECONDS (default 5 minutes).
 *
 * On failure the request proceeds normally — no forced logout.
 */
class DotNetRefreshToken
{
    private const REFRESH_BUFFER_SECONDS = 300; // 5 minutes

    public function __construct(protected DotNetAuthProvider $provider) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->shouldRefresh()) {
            try {
                $newToken = $this->provider->refreshToken();
                if ($newToken) {
                    Log::info('DotNet token refreshed automatically.');
                }
            } catch (\Throwable $e) {
                // Never disrupt the request on refresh failure.
                Log::warning('DotNet token auto-refresh failed: ' . $e->getMessage());
            }
        }

        return $next($request);
    }

    private function shouldRefresh(): bool
    {
        // Only act on DotNet sessions.
        if (!session()->has('dotnet_access_token')) {
            return false;
        }

        $expiresAt = session('dotnet_token_expires_at');
        if (!$expiresAt) {
            return false;
        }

        return (int) $expiresAt - time() <= self::REFRESH_BUFFER_SECONDS;
    }
}
