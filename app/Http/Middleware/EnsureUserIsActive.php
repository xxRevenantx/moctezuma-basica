<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $personaInactiva = $user?->isProfessor() && (! $user->persona_id || ! $user->persona?->status || in_array(mb_strtolower((string) $user->persona?->estado_laboral), ['baja', 'inactivo', 'suspendido'], true));

        if ($user && (! ($user->activo ?? true) || $personaInactiva)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada. Solicita acceso al administrador.',
            ]);
        }

        return $next($request);
    }
}
