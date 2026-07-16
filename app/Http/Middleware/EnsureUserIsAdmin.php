<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->is_admin || $request->user()?->rol_sistema === 'administrador',
            403,
            'No tienes permiso para acceder a esta sección administrativa.'
        );

        return $next($request);
    }
}
