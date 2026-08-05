<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! (bool) ($user->must_change_password ?? false)) {
            return $next($request);
        }

        if ($request->routeIs('user-password.edit') || $this->isPasswordSettingsLivewireRequest($request)) {
            return $next($request);
        }

        return redirect()
            ->route('user-password.edit')
            ->with('status', 'Debes sustituir la contraseña temporal antes de continuar.');
    }

    private function isPasswordSettingsLivewireRequest(Request $request): bool
    {
        if (! $request->routeIs('livewire.*') && ! $request->hasHeader('X-Livewire')) {
            return false;
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

        foreach (array_values(array_unique($paths)) as $path) {
            try {
                $matchedRoute = app('router')->getRoutes()->match(
                    Request::create('/'.ltrim($path, '/'), 'GET')
                );
            } catch (Throwable) {
                continue;
            }

            if ($matchedRoute->getName() === 'user-password.edit') {
                return true;
            }
        }

        return false;
    }
}
