# .NET Auth Integration Guide

This document explains how to swap the authentication provider from the current Laravel session-based implementation to an external .NET API.

---

## Architecture Overview

```
Controllers / UI
      │
      ▼
  AuthService          ← Single entry point (never bypass this)
      │
      ▼
AuthProviderInterface  ← Contract
      │
      ├── LaravelAuthProvider   (current, session-based)
      └── DotNetAuthProvider    (stub ready, activate with config)
```

The swap is **zero-code-change** in controllers — only config changes are needed.

---

## Step 1: Build Your .NET Auth API

Your .NET API must expose these endpoints:

### `POST /auth/login`
```json
Request:  { "email": "...", "password": "...", "remember": false }
Response: {
  "user_id": 1,
  "tenant_id": 5,
  "role": "customer",
  "display_name": "John Smith",
  "email": "john@acme.com",
  "session_token": "...",
  "expires_at": "2025-12-31T23:59:59Z",
  "permissions": ["contracts.view", "invoices.view"]
}
```

### `POST /auth/logout`
```json
Request headers: Authorization: Bearer {session_token}
Response: { "success": true }
```

### `GET /auth/validate`
```json
Request headers: Authorization: Bearer {session_token}
Response: Same session payload as login, or 401
```

---

## Step 2: Configure the .NET Provider

In your `.env`:
```env
AUTH_PROVIDER=dotnet
DOTNET_AUTH_API_BASE_URL=https://your-dotnet-api.com/auth
```

Or in `config/wrkplan.php`:
```php
'auth' => [
    'provider' => env('AUTH_PROVIDER', 'laravel'),
    'dotnet_api_base_url' => env('DOTNET_AUTH_API_BASE_URL'),
],
```

---

## Step 3: Implement the DotNetAuthProvider

The stub is at `app/Services/Auth/DotNetAuthProvider.php`. It already:
- Implements `AuthProviderInterface`
- Has the correct method signatures
- Uses `Illuminate\Http\Client\Factory` for HTTP calls

Uncomment and complete the HTTP calls in:
- `attempt()` — POST to `/login`
- `logout()` — POST to `/logout`
- `currentUserPayload()` — Read from PHP session
- `validateToken()` — GET to `/validate`

---

## Step 4: Session Payload Contract

**This payload shape MUST NOT change** — it is the contract between auth providers and the rest of the application:

```php
[
    'user_id'       => int,
    'tenant_id'     => int|null,      // null for admin roles
    'role'          => string,         // 'customer', 'admin', 'superadmin'
    'display_name'  => string,
    'email'         => string,
    'session_token' => string,         // 64-char random
    'expires_at'    => string,         // ISO 8601
    'permissions'   => string[],       // e.g. ['contracts.*', 'invoices.view']
]
```

The `User::toSessionPayload()` method produces this for Laravel. Your .NET API response must match exactly.

---

## Step 5: Password Migration

During migration, you may temporarily enable plain-text password fallback:
```env
AUTH_PLAIN_TEXT_PASSWORDS=true
```

This allows the `LaravelAuthProvider` to accept passwords from a `.NET` plaintext import. **Disable before go-live.**

---

## Step 6: AppServiceProvider Binding

The DI binding in `app/Providers/AppServiceProvider.php` reads config and binds the correct provider:

```php
$this->app->bind(AuthProviderInterface::class, function () {
    return match (config('wrkplan.auth.provider', 'laravel')) {
        'dotnet' => new DotNetAuthProvider(),
        default  => new LaravelAuthProvider(),
    };
});
```

No other code changes are needed after this binding takes effect.

---

## Rollback

To revert to Laravel auth:
```env
AUTH_PROVIDER=laravel
```
Then `php artisan config:clear`.

---

## Testing the Provider

```bash
php artisan tinker
>>> app(\App\Services\Auth\AuthService::class)->attempt('admin@wrkplan.com', 'password')
# Should return the session payload array

>>> app(\App\Services\Auth\AuthService::class)->getProviderName()
# Returns 'laravel' or 'dotnet'
```
