<?php

namespace App\Services\Auth;

/**
 * Auth Provider Interface
 *
 * FUTURE .NET INTEGRATION NOTE:
 * =====================================================
 * This interface defines the contract that BOTH the Laravel auth provider
 * and the future .NET auth provider must implement.
 *
 * To switch to .NET auth:
 * 1. Implement DotNetAuthProvider (already stubbed)
 * 2. Set AUTH_PROVIDER=dotnet in .env
 * 3. Set DOTNET_AUTH_API_BASE_URL to your .NET API URL
 * 4. The session payload contract (toSessionPayload) remains unchanged
 *
 * The standardized auth response contract:
 * {
 *   "user_id": int,
 *   "tenant_id": int|null,
 *   "role": "admin"|"customer"|"superadmin",
 *   "display_name": string,
 *   "email": string,
 *   "session_token": string|null,
 *   "expires_at": string|null (ISO 8601),
 *   "permissions": string[]
 * }
 * =====================================================
 */
interface AuthProviderInterface
{
    /**
     * Attempt to authenticate with given credentials.
     * Returns standardized session payload or null on failure.
     */
    public function attempt(string $email, string $password, bool $remember = false): ?array;

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void;

    /**
     * Get the currently authenticated user's session payload.
     */
    public function currentUserPayload(): ?array;

    /**
     * Check if a user is currently authenticated.
     */
    public function check(): bool;

    /**
     * Validate a session token (used for external provider token validation).
     */
    public function validateToken(string $token): ?array;
}
