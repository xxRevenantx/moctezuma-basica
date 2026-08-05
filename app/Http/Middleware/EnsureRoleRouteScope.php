<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureRoleRouteScope
{
    /** @var array<int,string> */
    private const PROFESSOR_ALLOWED_ROUTES = [
        'dashboard',
        'profile.edit',
        'user-password.edit',
        'appearance.edit',
        'two-factor.show',
        'docente.horario',
        'docente.calificaciones',
        'docente.fichas',
        'docente.entregas.pdf',
        'profesor.horario.pdf',
        'misrutas.fichas.grupo.pdf',
        'misrutas.fichas.alumno.pdf',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isProfessor()) {
            return $next($request);
        }

        abort_unless(
            $user->persona_id && $user->persona && $user->persona->status,
            403,
            'La cuenta docente no está vinculada a un profesor activo.'
        );

        $routeNames = $this->routeNamesToAuthorize($request);
        $allowed = $routeNames !== [] && collect($routeNames)->every(
            fn (string $routeName): bool => collect(self::PROFESSOR_ALLOWED_ROUTES)
                ->contains(fn (string $pattern): bool => Str::is($pattern, $routeName))
        );

        abort_unless(
            $allowed,
            403,
            'Tu cuenta docente solo puede acceder a Horarios, Calificaciones y, en preescolar, Fichas descriptivas.'
        );

        return $next($request);
    }

    /** @return array<int,string> */
    private function routeNamesToAuthorize(Request $request): array
    {
        if (! $request->routeIs('livewire.*') && ! $request->hasHeader('X-Livewire')) {
            $name = (string) ($request->route()?->getName() ?? '');

            return $name !== '' ? [$name] : [];
        }

        $paths = [];
        foreach ((array) $request->input('components', []) as $component) {
            $snapshot = $component['snapshot'] ?? null;
            if (! is_string($snapshot)) {
                continue;
            }

            $decoded = json_decode($snapshot, true);
            $path = trim((string) data_get($decoded, 'memo.path', ''), '/');
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        if ($paths === []) {
            $refererPath = trim((string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH), '/');
            if ($refererPath !== '') {
                $paths[] = $refererPath;
            }
        }

        $routeNames = [];
        foreach (array_values(array_unique($paths)) as $path) {
            try {
                $syntheticRequest = Request::create('/'.ltrim($path, '/'), 'GET');
                $matchedRoute = app('router')->getRoutes()->match($syntheticRequest);
                $routeName = (string) ($matchedRoute->getName() ?? '');
            } catch (Throwable) {
                return [];
            }

            if ($routeName === '') {
                return [];
            }

            $routeNames[] = $routeName;
        }

        return array_values(array_unique($routeNames));
    }
}
