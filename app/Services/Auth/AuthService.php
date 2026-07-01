<?php

namespace App\Services\Auth;

/**
 * AuthService - Single entry point for all authentication operations.
 *
 * This is the ONLY class that UI/Controllers should call for auth.
 * The underlying provider (Laravel or .NET) is swapped via config.
 *
 * Usage:
 *   app(AuthService::class)->attempt($email, $password)
 *   app(AuthService::class)->check()
 *   app(AuthService::class)->currentUserPayload()
 */
class AuthService
{
    public function __construct(
        protected AuthProviderInterface $provider
    ) {}

    public function attempt(string $email, string $password, bool $remember = false): ?array
    {
        return $this->provider->attempt($email, $password, $remember);
    }

    public function logout(): void
    {
        $this->provider->logout();
    }

    public function currentUserPayload(): ?array
    {
        return $this->provider->currentUserPayload();
    }

    public function check(): bool
    {
        return $this->provider->check();
    }

    public function validateToken(string $token): ?array
    {
        return $this->provider->validateToken($token);
    }

    public function getProviderName(): string
    {
        return config('wrkplan.auth.provider', 'laravel');
    }
}
