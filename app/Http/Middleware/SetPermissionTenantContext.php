<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionTenantContext
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            setPermissionsTeamId(
                $user->tenant_id
            );
        }

        try {
            return $next($request);
        } finally {
            setPermissionsTeamId(null);
        }
    }
}