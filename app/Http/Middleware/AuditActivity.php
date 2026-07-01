<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class AuditActivity
{
    protected array $skipRoutes = ['api.health', 'sanctum.csrf-cookie'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Log write operations
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    protected function logActivity(Request $request, $response): void
    {
        try {
            $user = Auth::user();
            AuditLog::create([
                'user_id'     => $user?->id,
                'tenant_id'   => $user?->tenant_id,
                'action'      => strtolower($request->method()),
                'module'      => $this->resolveModule($request->path()),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'description' => $request->method() . ' ' . $request->path(),
            ]);
        } catch (\Exception $e) {
            // Never let audit logging break the request
        }
    }

    protected function resolveModule(string $path): string
    {
        $segments = explode('/', $path);
        // /api/v1/{module}/... -> extract module
        return $segments[2] ?? 'unknown';
    }
}
