<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\AuthService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTokenAuth
{
    public function __construct(protected AuthService $authService) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::check()) {
            return $next($request);
        }

        $token = $this->extractToken($request);
        if ($token === null) {
            return $this->unauthorizedResponse();
        }

        $payload = $this->authService->validateToken($token);
        $userId = (int) ($payload['user_id'] ?? 0);

        if (!$payload || $userId <= 0) {
            return $this->unauthorizedResponse('The provided API session token is invalid or expired.');
        }

        $user = User::query()
            ->where('is_active', true)
            ->find($userId);

        if (!$user) {
            return $this->unauthorizedResponse('The authenticated user is no longer available.');
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('api_auth_payload', $payload);
        $request->attributes->set('api_session_token', $token);

        return $next($request);
    }

    protected function extractToken(Request $request): ?string
    {
        $bearerToken = trim((string) $request->bearerToken());
        if ($bearerToken !== '') {
            return $bearerToken;
        }

        $headerToken = trim((string) $request->header('X-Session-Token'));
        if ($headerToken !== '') {
            return $headerToken;
        }

        $inputToken = trim((string) $request->input('session_token', ''));
        if ($inputToken !== '') {
            return $inputToken;
        }

        return null;
    }

    protected function unauthorizedResponse(string $message = 'Authentication required. Provide a valid bearer token or X-Session-Token header.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => [
                'auth' => [$message],
            ],
        ], 401);
    }
}