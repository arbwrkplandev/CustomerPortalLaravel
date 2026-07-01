<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant Isolation Middleware
 * Ensures all requests are scoped to the authenticated user's tenant.
 */
class TenantScope
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->isCustomer()) {
            $tenantId = Auth::user()->tenant_id;
            if (!$tenantId) {
                abort(403, 'No tenant assigned to this account.');
            }
            // Make tenant_id available globally for the request
            app()->instance('current_tenant_id', $tenantId);
        }

        return $next($request);
    }
}
