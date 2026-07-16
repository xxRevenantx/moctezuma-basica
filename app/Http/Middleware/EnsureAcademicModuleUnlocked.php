<?php

namespace App\Http\Middleware;

use App\Models\WorkflowState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcademicModuleUnlocked
{
    public function handle(Request $request, Closure $next, string $module, string $context = 'global'): Response
    {
        if (! Schema::hasTable('workflow_states')) {
            return $next($request);
        }

        $state = WorkflowState::query()
            ->where('module', $module)
            ->where('context_key', $context)
            ->first();

        abort_if(
            $state?->status === WorkflowState::STATUS_CLOSED && ! $request->user()?->canAccess('flujos.gestionar'),
            423,
            'Este módulo se encuentra cerrado y no admite modificaciones.'
        );

        return $next($request);
    }
}
