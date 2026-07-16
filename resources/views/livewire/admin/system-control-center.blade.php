<div class="space-y-6">
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-lime-400/15 blur-3xl"></div>

        <div class="relative bg-gradient-to-br from-slate-950 via-sky-950 to-emerald-950 px-6 py-8 text-white sm:px-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-sky-100 backdrop-blur">
                        <flux:icon name="shield-check" class="size-4" />
                        Supervisión y estabilidad
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Centro de control</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Revisa la integridad académica, auditoría, respaldos, permisos, papelera y estados de autorización sin cambiar el flujo de captura de los módulos existentes.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:min-w-[620px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Críticas</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format($criticalIssues) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Advertencias</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format($warningIssues) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Avisos nuevos</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format($unreadNotifications) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-bold uppercase text-slate-300">Estado</p>
                        <p class="mt-2 text-sm font-black {{ $criticalIssues > 0 ? 'text-amber-200' : 'text-emerald-200' }}">
                            {{ $criticalIssues > 0 ? 'Requiere revisión' : 'Operación estable' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $tabs = [
            'resumen' => ['Resumen', 'layout-dashboard'],
            'integridad' => ['Integridad', 'document-magnifying-glass'],
            'asistente' => ['Asistente', 'sparkles'],
            'notificaciones' => ['Avisos', 'bell'],
            'auditoria' => ['Auditoría', 'history'],
            'respaldos' => ['Respaldos', 'database-backup'],
            'papelera' => ['Papelera', 'trash-2'],
            'permisos' => ['Permisos', 'key'],
            'configuracion' => ['Configuración', 'settings'],
            'flujos' => ['Autorizaciones', 'badge-check'],
            'cierre' => ['Cierre de ciclo', 'check-circle'],
        ];
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex gap-2 overflow-x-auto pb-1">
            @foreach ($tabs as $key => [$label, $icon])
                <button type="button" wire:click="setTab('{{ $key }}')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition {{ $tab === $key ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800' }}">
                    <flux:icon :name="$icon" class="size-4" />
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>

    @if ($tab === 'resumen')
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($health as $key => $item)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">{{ str($key)->replace('_', ' ')->title() }}</p>
                            <p class="mt-2 font-black text-slate-900 dark:text-white">{{ $item['value'] ?? 'Sin datos' }}</p>
                        </div>
                        <span class="inline-flex size-9 items-center justify-center rounded-xl {{ ($item['ok'] ?? false) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                            <flux:icon :name="($item['ok'] ?? false) ? 'check-circle' : 'triangle-alert'" class="size-5" />
                        </span>
                    </div>
                    @if (!empty($item['detail']))
                        <p class="mt-3 break-words text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $item['detail'] }}</p>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">Prioridades detectadas</h2>
                        <p class="mt-1 text-sm text-slate-500">No se realizan correcciones automáticas.</p>
                    </div>
                    <button type="button" wire:click="runIntegrity" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-black text-white hover:bg-sky-700 disabled:opacity-60">
                        <flux:icon name="arrow-path" class="size-4" wire:loading.class="animate-spin" wire:target="runIntegrity" />
                        Revisar ahora
                    </button>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse (collect($issues)->take(6) as $issue)
                        <div class="rounded-2xl border p-4 {{ $issue['severity'] === 'critical' ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30' : ($issue['severity'] === 'warning' ? 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30' : 'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/30') }}">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/80 dark:bg-neutral-900/60">
                                    <flux:icon :name="$issue['severity'] === 'critical' ? 'x-circle' : ($issue['severity'] === 'warning' ? 'triangle-alert' : 'info')" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="font-black text-slate-900 dark:text-white">{{ $issue['title'] }}</h3>
                                        <span class="rounded-full bg-white/80 px-2.5 py-1 text-xs font-black dark:bg-neutral-900/60">{{ number_format($issue['count']) }}</span>
                                    </div>
                                    <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">{{ $issue['description'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
                            <div class="flex items-center gap-3 font-black"><flux:icon name="shield-check" class="size-5" /> No se detectaron incidencias activas.</div>
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Acciones seguras</h2>
                <div class="mt-5 space-y-3">
                    <button type="button" wire:click="createBackup" wire:loading.attr="disabled"
                        class="flex w-full items-center justify-between rounded-2xl border border-slate-200 p-4 text-left transition hover:border-sky-300 hover:bg-sky-50 dark:border-neutral-700 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                        <span class="flex items-center gap-3"><flux:icon name="database-backup" class="size-5 text-sky-600" /><span><strong class="block text-slate-900 dark:text-white">Crear respaldo verificado</strong><small class="text-slate-500">Alumnos, calificaciones y manifiesto SHA-256</small></span></span>
                        <flux:icon name="chevron-right" class="size-4" />
                    </button>
                    <button type="button" wire:click="setTab('papelera')"
                        class="flex w-full items-center justify-between rounded-2xl border border-slate-200 p-4 text-left transition hover:border-sky-300 hover:bg-sky-50 dark:border-neutral-700 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                        <span class="flex items-center gap-3"><flux:icon name="arrow-uturn-left" class="size-5 text-sky-600" /><span><strong class="block text-slate-900 dark:text-white">Restaurar eliminados</strong><small class="text-slate-500">Recupera registros sin reconstruirlos manualmente</small></span></span>
                        <flux:icon name="chevron-right" class="size-4" />
                    </button>
                    <button type="button" wire:click="setTab('cierre')"
                        class="flex w-full items-center justify-between rounded-2xl border border-slate-200 p-4 text-left transition hover:border-sky-300 hover:bg-sky-50 dark:border-neutral-700 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                        <span class="flex items-center gap-3"><flux:icon name="clipboard-document-check" class="size-5 text-sky-600" /><span><strong class="block text-slate-900 dark:text-white">Preparar cierre</strong><small class="text-slate-500">Valida riesgos antes de usar el cierre existente</small></span></span>
                        <flux:icon name="chevron-right" class="size-4" />
                    </button>
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'integridad')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">Integridad académica</h2>
                    <p class="mt-1 text-sm text-slate-500">Detecta inconsistencias y abre el módulo relacionado para corregirlas manualmente.</p>
                </div>
                <button type="button" wire:click="runIntegrity" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-black text-white hover:bg-sky-700 disabled:opacity-60">
                    <flux:icon name="document-magnifying-glass" class="size-4" /> Ejecutar revisión completa
                </button>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @forelse ($issues as $issue)
                    <article class="rounded-2xl border border-slate-200 p-5 dark:border-neutral-700">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl {{ $issue['severity'] === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : ($issue['severity'] === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300') }}">
                                    <flux:icon :name="$issue['severity'] === 'critical' ? 'x-circle' : ($issue['severity'] === 'warning' ? 'triangle-alert' : 'info')" class="size-5" />
                                </span>
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">{{ $issue['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-5 text-slate-500 dark:text-slate-400">{{ $issue['description'] }}</p>
                                </div>
                            </div>
                            <span class="rounded-xl bg-slate-100 px-3 py-1.5 text-lg font-black text-slate-900 dark:bg-neutral-800 dark:text-white">{{ number_format($issue['count']) }}</span>
                        </div>
                        @if (!empty($issue['samples']))
                            <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-neutral-800 dark:text-slate-400">Ejemplos: {{ implode(', ', $issue['samples']) }}</p>
                        @endif
                        @if (!empty($issue['url']))
                            <a href="{{ $issue['url'] }}" class="mt-4 inline-flex items-center gap-2 text-sm font-black text-sky-700 hover:text-sky-800 dark:text-sky-300">
                                Abrir módulo relacionado <flux:icon name="arrow-up-right" class="size-4" />
                            </a>
                        @endif
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center dark:border-emerald-900 dark:bg-emerald-950/30">
                        <flux:icon name="shield-check" class="mx-auto size-10 text-emerald-600" />
                        <h3 class="mt-3 font-black text-emerald-900 dark:text-emerald-100">Sin incidencias detectadas</h3>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($tab === 'asistente')
        <section class="grid gap-5 xl:grid-cols-[1.15fr_.85fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-start gap-4">
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-emerald-500 text-white shadow-sm">
                        <flux:icon name="sparkles" class="size-6" />
                    </span>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Asistente de consulta segura</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Interpreta preguntas frecuentes mediante reglas controladas. No genera SQL libre, no cambia datos y no toma decisiones académicas.
                        </p>
                    </div>
                </div>

                <form wire:submit="askAssistant" class="mt-6 space-y-3">
                    <label class="grid gap-2 text-sm font-black text-slate-700 dark:text-slate-200">
                        Pregunta sobre la operación del sistema
                        <textarea wire:model="assistantQuery" rows="4" maxlength="300"
                            placeholder="Ejemplo: ¿Qué profesores tienen choque de horario?"
                            class="w-full resize-none rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-normal outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100 dark:border-neutral-700 dark:bg-neutral-950 dark:focus:ring-sky-950"></textarea>
                    </label>
                    @error('assistantQuery')
                        <p class="text-sm font-bold text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-400">Máximo 300 caracteres. Las consultas quedan registradas en auditoría.</p>
                        <button type="submit" wire:loading.attr="disabled" wire:target="askAssistant"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-black text-white transition hover:bg-sky-700 disabled:opacity-60">
                            <flux:icon name="sparkles" class="size-4" />
                            Consultar
                        </button>
                    </div>
                </form>

                @if ($assistantResponse)
                    @php
                        $assistantTone = match ($assistantResponse['severity'] ?? 'info') {
                            'critical' => 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30',
                            'warning' => 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30',
                            'success' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30',
                            default => 'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/30',
                        };
                    @endphp
                    <div class="mt-6 rounded-2xl border p-5 {{ $assistantTone }}">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/80 dark:bg-neutral-900/70">
                                <flux:icon :name="($assistantResponse['severity'] ?? 'info') === 'critical' ? 'x-circle' : (($assistantResponse['severity'] ?? 'info') === 'warning' ? 'triangle-alert' : (($assistantResponse['severity'] ?? 'info') === 'success' ? 'check-circle' : 'info'))" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ $assistantResponse['title'] ?? 'Resultado' }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $assistantResponse['summary'] ?? '' }}</p>
                                @if (!empty($assistantResponse['details']))
                                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                        @foreach ($assistantResponse['details'] as $detail)
                                            <li class="flex items-start gap-2"><span class="mt-2 size-1.5 shrink-0 rounded-full bg-current"></span><span>{{ $detail }}</span></li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if (!empty($assistantResponse['url']))
                                    <a href="{{ $assistantResponse['url'] }}" class="mt-4 inline-flex items-center gap-2 text-sm font-black text-sky-700 hover:text-sky-800 dark:text-sky-300">
                                        Abrir módulo relacionado <flux:icon name="arrow-up-right" class="size-4" />
                                    </a>
                                @endif
                                <p class="mt-4 border-t border-current/10 pt-3 text-xs text-slate-500 dark:text-slate-400">{{ $assistantResponse['notice'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </article>

            <aside class="space-y-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <h3 class="font-black text-slate-900 dark:text-white">Consultas de ejemplo</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ([
                            '¿Qué profesores tienen choque de horario?',
                            '¿Cuántos alumnos no tienen documentos?',
                            'Muéstrame las materias sin profesor.',
                            '¿Hay alumnos sin calificaciones?',
                            'Dame un resumen de incidencias críticas.',
                        ] as $example)
                            <button type="button" wire:click="useAssistantExample(@js($example))"
                                class="w-full rounded-xl border border-slate-200 px-3 py-3 text-left text-sm font-bold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                                {{ $example }}
                            </button>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/25">
                    <div class="flex items-start gap-3">
                        <flux:icon name="shield-check" class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300" />
                        <div>
                            <h3 class="font-black text-emerald-900 dark:text-emerald-100">Protecciones activas</h3>
                            <p class="mt-2 text-sm leading-6 text-emerald-800 dark:text-emerald-200">
                                El asistente solo consulta resultados de integridad, entrega recomendaciones y dirige al módulo correcto. Nunca corrige, elimina ni autoriza información por sí mismo.
                            </p>
                        </div>
                    </div>
                </article>
            </aside>
        </section>
    @endif

    @if ($tab === 'notificaciones')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">Notificaciones internas</h2>
                    <p class="mt-1 text-sm text-slate-500">Los avisos se generan por reglas verificables, no por decisiones automáticas de IA.</p>
                </div>
                <label class="grid gap-1 text-sm font-bold text-slate-600 dark:text-slate-300">
                    Severidad
                    <select wire:model.live="notificationSeverity" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <option value="">Todas</option>
                        <option value="critical">Crítica</option>
                        <option value="warning">Advertencia</option>
                        <option value="info">Informativa</option>
                    </select>
                </label>
            </div>

            <div class="mt-6 space-y-3">
                @forelse ($notifications as $notification)
                    <article wire:key="notification-{{ $notification->id }}" class="rounded-2xl border p-4 {{ $notification->read_at ? 'border-slate-200 bg-slate-50/50 dark:border-neutral-800 dark:bg-neutral-900' : 'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/20' }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-neutral-800">
                                    <flux:icon :name="$notification->severity === 'critical' ? 'x-circle' : ($notification->severity === 'warning' ? 'triangle-alert' : 'bell')" class="size-5" />
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-black text-slate-900 dark:text-white">{{ $notification->title }}</h3>
                                        @if (!$notification->read_at)<span class="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-black uppercase text-white">Nuevo</span>@endif
                                    </div>
                                    <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">{{ $notification->message }}</p>
                                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if ($notification->action_url)
                                    <a href="{{ $notification->action_url }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">Abrir</a>
                                @endif
                                @if (!$notification->read_at)
                                    <button type="button" wire:click="markNotificationRead({{ $notification->id }})" class="rounded-xl bg-sky-600 px-3 py-2 text-xs font-black text-white">Marcar leída</button>
                                @endif
                                <button type="button" wire:click="dismissNotification({{ $notification->id }})" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 dark:border-neutral-700 dark:text-slate-300">Ocultar</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500 dark:border-neutral-700">No hay avisos activos.</div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($tab === 'auditoria')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">Historial general de modificaciones</h2>
                    <p class="mt-1 text-sm text-slate-500">Registra creación, edición, eliminación, restauración, permisos, respaldos y transiciones.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input type="search" wire:model.live.debounce.400ms="auditSearch" placeholder="Buscar acción, ruta o modelo" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                    <select wire:model.live="auditModule" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <option value="">Todos los módulos</option>
                        @foreach (['alumnos','personal','academico','calificaciones','documentos','horarios','usuarios','respaldos','integridad','flujos','papelera','configuracion'] as $module)
                            <option value="{{ $module }}">{{ str($module)->title() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-700">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-700">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wider text-slate-500 dark:bg-neutral-800 dark:text-slate-300">
                        <tr><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Usuario</th><th class="px-4 py-3">Acción</th><th class="px-4 py-3">Módulo</th><th class="px-4 py-3">Registro / ruta</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($audits as $audit)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $audit->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3"><strong class="block text-slate-900 dark:text-white">{{ $audit->user?->name ?? 'Sistema' }}</strong><small class="text-slate-400">{{ $audit->ip }}</small></td>
                                <td class="px-4 py-3"><span class="rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-700 dark:bg-neutral-800 dark:text-slate-200">{{ $audit->action }}</span></td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $audit->module }}</td>
                                <td class="max-w-md px-4 py-3 text-xs text-slate-500"><span class="block truncate">{{ class_basename($audit->auditable_type ?: '') }} {{ $audit->auditable_id ? '#'.$audit->auditable_id : '' }}</span><span class="block truncate">{{ $audit->route }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Todavía no hay movimientos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'respaldos')
        <section class="grid gap-5 xl:grid-cols-[.7fr_1.3fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300"><flux:icon name="database-backup" class="size-6" /></div>
                <h2 class="mt-4 text-2xl font-black text-slate-900 dark:text-white">Respaldo automático</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Genera dos libros Excel completos y un manifiesto con tamaño y hash SHA-256. En producción, configura el cron de Laravel para ejecutarlo diariamente.</p>
                <button type="button" wire:click="createBackup" wire:loading.attr="disabled" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-3 text-sm font-black text-white hover:bg-sky-700 disabled:opacity-60">
                    <flux:icon name="database-backup" class="size-5" />
                    <span wire:loading.remove wire:target="createBackup">Crear respaldo ahora</span>
                    <span wire:loading wire:target="createBackup">Generando y verificando...</span>
                </button>
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    <strong>cPanel:</strong> programa <code class="font-black">php artisan schedule:run</code> cada minuto. Laravel decidirá cuándo ejecutar cada tarea.
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Historial de respaldos</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($backups as $backup)
                        <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex size-10 items-center justify-center rounded-xl {{ $backup->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : ($backup->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-amber-100 text-amber-700') }}">
                                        <flux:icon :name="$backup->status === 'completed' ? 'check-circle' : ($backup->status === 'failed' ? 'x-circle' : 'arrow-path')" class="size-5" />
                                    </span>
                                    <div><strong class="block text-slate-900 dark:text-white">{{ $backup->created_at?->format('d/m/Y H:i') }}</strong><small class="text-slate-500">{{ $backup->path ?: 'Sin ruta' }}</small></div>
                                </div>
                                <div class="text-left sm:text-right"><span class="block text-xs font-black uppercase text-slate-500">{{ $backup->status }}</span><small class="text-slate-400">{{ $backup->size_bytes ? number_format($backup->size_bytes / 1024 / 1024, 2).' MB' : '' }}</small></div>
                            </div>
                            @if ($backup->error)<p class="mt-3 rounded-xl bg-red-50 p-3 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-300">{{ $backup->error }}</p>@endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500 dark:border-neutral-700">No se han creado respaldos automáticos.</div>
                    @endforelse
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'papelera')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="grid gap-5 xl:grid-cols-[1fr_320px]">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">Papelera administrativa</h2>
                    <p class="mt-1 text-sm text-slate-500">Los elementos eliminados dejan de aparecer en los módulos, pero pueden restaurarse desde aquí.</p>
                    <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-700">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-700">
                            <thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500 dark:bg-neutral-800"><tr><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Registro</th><th class="px-4 py-3">Eliminado</th><th class="px-4 py-3">Acciones</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @forelse ($trash as $item)
                                    <tr wire:key="trash-{{ $item['type'] }}-{{ $item['id'] }}">
                                        <td class="px-4 py-3 font-black text-slate-600 dark:text-slate-300">{{ str($item['type'])->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3 text-slate-900 dark:text-white">{{ $item['label'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ optional($item['deleted_at'])->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3"><div class="flex gap-2"><button type="button" wire:click="restoreItem('{{ $item['type'] }}', {{ $item['id'] }})" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white">Restaurar</button><button type="button" wire:click="forceDeleteItem('{{ $item['type'] }}', {{ $item['id'] }})" class="rounded-xl border border-red-200 px-3 py-2 text-xs font-black text-red-700 dark:border-red-900 dark:text-red-300">Eliminar definitivo</button></div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">La papelera está vacía.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-900 dark:bg-red-950/20">
                    <flux:icon name="triangle-alert" class="size-6 text-red-600" />
                    <h3 class="mt-3 font-black text-red-900 dark:text-red-100">Eliminación permanente</h3>
                    <p class="mt-2 text-sm leading-5 text-red-700 dark:text-red-300">Solo se ejecuta cuando escribes exactamente <strong>ELIMINAR</strong>. Los archivos físicos de documentos también serán borrados.</p>
                    <input type="text" wire:model="forceDeleteConfirmation" placeholder="Escribe ELIMINAR" class="mt-4 w-full rounded-xl border border-red-300 bg-white px-3 py-2.5 text-sm font-black dark:border-red-900 dark:bg-neutral-900">
                    @error('forceDeleteConfirmation')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </aside>
            </div>
        </section>
    @endif

    @if ($tab === 'permisos')
        <section class="grid gap-5 xl:grid-cols-[340px_1fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Usuarios</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($users as $user)
                        <button type="button" wire:click="selectUser({{ $user->id }})" class="flex w-full items-center gap-3 rounded-xl border p-3 text-left transition {{ $selectedUserId === $user->id ? 'border-sky-400 bg-sky-50 dark:border-sky-800 dark:bg-sky-950/20' : 'border-slate-200 hover:bg-slate-50 dark:border-neutral-700 dark:hover:bg-neutral-800' }}">
                            <span class="inline-flex size-9 items-center justify-center rounded-xl bg-slate-100 font-black text-slate-700 dark:bg-neutral-800 dark:text-slate-200">{{ $user->initials() }}</span>
                            <span class="min-w-0"><strong class="block truncate text-sm text-slate-900 dark:text-white">{{ $user->name }}</strong><small class="block truncate text-slate-500">{{ $user->roleLabel() }}</small></span>
                        </button>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                @if ($selectedUserId)
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200">Rol base
                            <select wire:model="selectedRole" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                @foreach ($roles as $role => $definition)<option value="{{ $role }}">{{ $definition['label'] }}</option>@endforeach
                            </select>
                        </label>
                        <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                            <label class="flex items-center justify-between gap-3 text-sm font-black text-slate-700 dark:text-slate-200"><span>Administrador total</span><input type="checkbox" wire:model="selectedIsAdmin" class="size-5 rounded border-slate-300 text-sky-600"></label>
                            <label class="flex items-center justify-between gap-3 text-sm font-black text-slate-700 dark:text-slate-200"><span>Cuenta activa</span><input type="checkbox" wire:model="selectedActive" class="size-5 rounded border-slate-300 text-sky-600"></label>
                        </div>
                    </div>
                    @error('selectedIsAdmin')<p class="mt-3 text-sm font-bold text-red-600">{{ $message }}</p>@enderror

                    <div class="mt-6">
                        <h3 class="font-black text-slate-900 dark:text-white">Permisos adicionales</h3>
                        <p class="mt-1 text-sm text-slate-500">Se suman al rol base. El administrador total siempre tiene todos los permisos.</p>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($permissions as $permission => $label)
                                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-neutral-700">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission }}" class="mt-0.5 size-4 rounded border-slate-300 text-sky-600">
                                    <span><strong class="block text-slate-800 dark:text-slate-100">{{ $label }}</strong><small class="text-slate-400">{{ $permission }}</small></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" wire:click="arrow-down-on-squareUserAccess" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-3 text-sm font-black text-white hover:bg-sky-700"><flux:icon name="arrow-down-on-square" class="size-4" /> Guardar accesos</button>
                @else
                    <div class="flex min-h-80 flex-col items-center justify-center text-center"><flux:icon name="user-circle" class="size-12 text-slate-300" /><h3 class="mt-4 text-xl font-black text-slate-900 dark:text-white">Selecciona un usuario</h3><p class="mt-2 text-sm text-slate-500">Elige una cuenta para configurar su rol y permisos.</p></div>
                @endif
            </article>
        </section>
    @endif

    @if ($tab === 'configuracion')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div>
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Configuración central</h2>
                <p class="mt-1 text-sm text-slate-500">Las plantillas actuales siguen funcionando; estos valores quedan disponibles para migrarlas gradualmente sin cambios masivos.</p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200 md:col-span-2">Institución<input type="text" wire:model="configuration.institution_name" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-900"></label>
                <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200 md:col-span-2">Lugar predeterminado<input type="text" wire:model="configuration.place" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-900"></label>
                <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200">Color principal<input type="color" wire:model="configuration.primary_color" class="h-11 w-full rounded-xl border border-slate-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-900"></label>
                <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200">Color secundario<input type="color" wire:model="configuration.secondary_color" class="h-11 w-full rounded-xl border border-slate-300 bg-white p-1 dark:border-neutral-700 dark:bg-neutral-900"></label>
                <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200">Retención de respaldos<input type="number" wire:model="configuration.backup_retention_days" min="1" max="365" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-900"></label>
                <label class="grid gap-1.5 text-sm font-black text-slate-700 dark:text-slate-200">Modo de IA<select wire:model="configuration.ai_mode" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-900"><option value="suggest_only">Solo sugerir</option><option value="prepare_confirm">Preparar y pedir confirmación</option></select></label>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 p-5 dark:border-neutral-700">
                <h3 class="font-black text-slate-900 dark:text-white">Márgenes predeterminados (mm)</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (['margin_top' => 'Superior','margin_right' => 'Derecho','margin_bottom' => 'Inferior','margin_left' => 'Izquierdo'] as $field => $label)
                        <label class="grid gap-1.5 text-sm font-bold text-slate-600 dark:text-slate-300">{{ $label }}<input type="number" step="0.5" wire:model="configuration.{{ $field }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-900"></label>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-5">
                <label class="flex items-center gap-3 text-sm font-black text-slate-700 dark:text-slate-200"><input type="checkbox" wire:model="configuration.show_cycle" class="size-5 rounded border-slate-300 text-sky-600"> Mostrar ciclo escolar en documentos nuevos</label>
                <label class="flex items-center gap-3 text-sm font-black text-slate-700 dark:text-slate-200"><input type="checkbox" wire:model="configuration.notification_channels" value="system" class="size-5 rounded border-slate-300 text-sky-600"> Notificaciones dentro del sistema</label>
            </div>

            @if ($errors->any())<div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/20 dark:text-red-300">Revisa los campos marcados antes de guardar.</div>@endif
            <button type="button" wire:click="arrow-down-on-squareConfiguration" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-3 text-sm font-black text-white hover:bg-sky-700"><flux:icon name="arrow-down-on-square" class="size-4" /> Guardar configuración</button>
        </section>
    @endif

    @if ($tab === 'flujos')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white">Estados de revisión y autorización</h2>
            <p class="mt-1 text-sm text-slate-500">La activación es gradual: primero sirve como control visible. El middleware de bloqueo queda disponible para aplicarlo módulo por módulo.</p>

            <div class="mt-6 grid gap-5 lg:grid-cols-3">
                @foreach ($workflows as $module => $state)
                    <article class="rounded-2xl border border-slate-200 p-5 dark:border-neutral-700">
                        <div class="flex items-center justify-between gap-3"><h3 class="text-lg font-black text-slate-900 dark:text-white">{{ str($module)->replace('_', ' ')->title() }}</h3><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase text-slate-700 dark:bg-neutral-800 dark:text-slate-200">{{ $state->status }}</span></div>
                        <div class="mt-5 grid grid-cols-2 gap-2">
                            @foreach (['borrador' => 'Borrador','revision' => 'En revisión','autorizado' => 'Autorizar','cerrado' => 'Cerrar'] as $status => $label)
                                <button type="button" wire:click="transitionWorkflow('{{ $module }}', '{{ $status }}')" class="rounded-xl border px-3 py-2.5 text-xs font-black {{ $state->status === $status ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        @if ($state->closed_at)<p class="mt-4 text-xs text-slate-400">Cerrado {{ $state->closed_at->diffForHumans() }}</p>@endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($tab === 'cierre')
        <section class="grid gap-5 xl:grid-cols-[1fr_380px]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Asistente previo al cierre de ciclo</h2>
                <p class="mt-1 text-sm text-slate-500">No reemplaza el servicio de cierre existente; agrega una revisión previa, respaldo y autorización para reducir errores.</p>

                <div class="mt-6 space-y-3">
                    @foreach ([
                        ['1','Revisar integridad','Alumnos, grupos, generaciones, horarios, profesores, calificaciones y expedientes.'],
                        ['2','Atender incidencias críticas','Las advertencias pueden aceptarse, pero las críticas deben revisarse.'],
                        ['3','Crear respaldo','Conserva alumnos, movimientos, calificaciones y bitácora antes de ejecutar.'],
                        ['4','Autorizar y cerrar','Usa el estado Cierre de ciclo y después ejecuta el flujo actual desde Ciclos/Generales.'],
                    ] as [$number,$title,$description])
                        <div class="flex gap-4 rounded-2xl border border-slate-200 p-4 dark:border-neutral-700"><span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-600 font-black text-white">{{ $number }}</span><div><h3 class="font-black text-slate-900 dark:text-white">{{ $title }}</h3><p class="mt-1 text-sm text-slate-500">{{ $description }}</p></div></div>
                    @endforeach
                </div>

                @if ($closureMessage)
                    <div class="mt-5 rounded-2xl border p-4 font-bold {{ $criticalIssues > 0 ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/20 dark:text-red-300' : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-300' }}">{{ $closureMessage }}</div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" wire:click="prepareClosure" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-3 text-sm font-black text-white"><flux:icon name="clipboard-document-check" class="size-4" /> Preparar cierre</button>
                    <button type="button" wire:click="createBackup" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200"><flux:icon name="database-backup" class="size-4" /> Respaldar</button>
                    @if (Route::has('misrutas.ciclos'))<a href="{{ route('misrutas.ciclos') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200"><flux:icon name="arrow-up-right" class="size-4" /> Abrir ciclos</a>@endif
                </div>
            </article>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Criterio de salida</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-800"><span>Incidencias críticas</span><strong>{{ number_format($criticalIssues) }}</strong></div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-800"><span>Advertencias</span><strong>{{ number_format($warningIssues) }}</strong></div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 dark:bg-neutral-800"><span>Respaldo</span><strong>{{ ($health['backup']['ok'] ?? false) ? 'Disponible' : 'Pendiente' }}</strong></div>
                </div>
                <div class="mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm leading-5 text-sky-900 dark:border-sky-900 dark:bg-sky-950/20 dark:text-sky-200">
                    El sistema no mueve ni promueve alumnos desde este asistente. La acción final permanece en los servicios actuales para conservar el comportamiento conocido.
                </div>
            </aside>
        </section>
    @endif
</div>
