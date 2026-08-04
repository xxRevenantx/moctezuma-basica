<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $route = $request->route();
        $name = (string) ($route?->getName() ?? '');
        $permission = null;

        if ($name === 'submodulos.accion') {
            $accion = (string) $route?->parameter('accion');
            $permission = config("route_permissions.submodules.{$accion}");
        }

        if (! $permission && $name !== '') {
            foreach ((array) config('route_permissions.names', []) as $pattern => $candidate) {
                if (Str::is($pattern, $name)) {
                    $permission = $candidate;
                    break;
                }
            }
        }

        if ($permission) {
            $permissions = array_values(array_filter((array) $permission, 'is_string'));

            abort_unless(
                collect($permissions)->contains(fn (string $candidate): bool => $user->canAccess($candidate)),
                403,
                'No tienes permiso para acceder a este módulo.'
            );
        }

        return $next($request);
    }
}
