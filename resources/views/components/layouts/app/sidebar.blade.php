<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')

    <x-head.tinymce-config />
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    @php
        $user = auth()->user();
        $isAdmin = (bool) ($user?->is_admin ?? false);
        $isProfessor = (bool) $user?->isProfessor();
        $teacherLevels = $isProfessor ? app(\App\Services\TeacherAcademicScopeService::class)->assignedLevels($user) : collect();
        $canAccessAdministration = (bool) $user?->canAccess('administracion.acceder');
        $canAccessIntegrity = (bool) $user?->canAccess('integridad.consultar');
        $canAccessTracking = (bool) $user?->canAccess('seguimiento.consultar');
        $canAccessAnalytics = (bool) $user?->canAccess('analitica.consultar');

        $systemUnread = $canAccessAdministration
            ? app(\App\Services\SystemNotificationService::class)->unreadCount($user?->id)
            : 0;

        $integridadAbiertos = $canAccessIntegrity && \Illuminate\Support\Facades\Schema::hasTable('integridad_academica_casos')
            ? \App\Models\IntegridadAcademicaCaso::query()->abiertos()->count()
            : 0;

        $alertasAcademicas = $canAccessTracking && \Illuminate\Support\Facades\Schema::hasTable('alertas_academicas')
            ? \App\Models\AlertaAcademica::query()->where('estado', 'pendiente')->count()
            : 0;

        $alertasDirectivas = $canAccessAnalytics && \Illuminate\Support\Facades\Schema::hasTable('analitica_institucional_alertas')
            ? \App\Models\AnaliticaInstitucionalAlerta::query()->activas()->count()
            : 0;

        $gestionEscolarExpanded = request()->routeIs(
            'misrutas.alumnos',
            'misrutas.inscripcion',
            'misrutas.tutores',
        );

        $academicaExpanded = request()->routeIs(
            'misrutas.integridad-academica',
            'misrutas.seguimiento-academico',
            'misrutas.analitica-institucional',
            'misrutas.analitica.*',
        );

        $estructuraExpanded = request()->routeIs(
            'misrutas.escuela',
            'misrutas.ciclos',
            'misrutas.niveles',
            'misrutas.grados',
            'misrutas.generaciones',
            'misrutas.grupos',
            'misrutas.periodos',
            'misrutas.materias',
        );

        $personalExpanded = request()->routeIs(
            'misrutas.personal',
            'misrutas.role-persona',
            'misrutas.plantilla',
            'misrutas.profesores',
            'misrutas.autoridades',
            'misrutas.expedientes-personal*',
        );

        $documentacionExpanded = request()->routeIs(
            'misrutas.expedientes',
            'misrutas.expedientes.*',
            'misrutas.constancias*',
            'misrutas.oficios*',
        );

        $mediaSuperiorExpanded = request()->routeIs(
            'misrutas.semestres',
            'media-superior.documentos.*',
        );

        $administracionExpanded = request()->routeIs(
            'misrutas.centro-control',
            'misrutas.respaldos-academicos',
        );
    @endphp

    <flux:sidebar sticky collapsible
        class="app-sidebar border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
        x-data="sidebarNavigation"
        x-on:keydown.window.prevent.ctrl.k="$refs.sidebarSearch?.focus()"
        x-on:keydown.window.prevent.meta.k="$refs.sidebarSearch?.focus()">

        {{-- Encabezado compacto y control de colapso --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}"
                class="group flex min-w-0 flex-1 items-center gap-2 rounded-xl px-1 py-1 transition hover:bg-zinc-100 dark:hover:bg-white/5 in-data-flux-sidebar-collapsed-desktop:justify-center"
                wire:navigate aria-label="Ir al inicio de Moctezuma">
                <span
                    class="relative flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#006492] text-sm font-black text-white shadow-sm">
                    M
                    <span class="absolute inset-x-0 bottom-0 h-1 bg-[#88AC2E]"></span>
                </span>

                <span class="min-w-0 in-data-flux-sidebar-collapsed-desktop:hidden">
                    <span class="block truncate text-sm font-bold text-zinc-900 dark:text-white">Moctezuma</span>
                    <span class="block truncate text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                        {{ $user?->roleLabel() ?? 'Usuario' }}
                    </span>
                </span>
            </a>

            <flux:sidebar.collapse class="max-lg:hidden"
                tooltip="Contraer menú" />

            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" aria-label="Cerrar menú" />
        </div>

        {{-- Acción principal --}}
        @if ($isProfessor)
            <a href="{{ route('docente.horario') }}"
                class="sidebar-primary-action inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#006492] px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#00547a] focus:outline-none focus:ring-2 focus:ring-[#006492]/40 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:px-0"
                wire:navigate aria-label="Consultar mi horario" title="Mi horario">
                <flux:icon name="calendar-days" class="size-4 shrink-0" />
                <span class="in-data-flux-sidebar-collapsed-desktop:hidden">Mi horario</span>
            </a>
        @else
            <a href="{{ route('misrutas.inscripcion') }}"
                class="sidebar-primary-action inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#006492] px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#00547a] focus:outline-none focus:ring-2 focus:ring-[#006492]/40 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:px-0"
                wire:navigate aria-label="Registrar un nuevo alumno" title="Nuevo alumno">
                <flux:icon name="user-plus" class="size-4 shrink-0" />
                <span class="in-data-flux-sidebar-collapsed-desktop:hidden">Nuevo alumno</span>
            </a>
        @endif

        {{-- Buscador de módulos --}}
        <div class="relative in-data-flux-sidebar-collapsed-desktop:hidden">
            <flux:icon name="magnifying-glass"
                class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />

            <input x-ref="sidebarSearch" x-model.debounce.120ms="query" x-on:keydown.escape="clearSearch"
                type="search" autocomplete="off" placeholder="Buscar módulo..."
                aria-label="Buscar un módulo del sistema"
                class="h-10 w-full rounded-xl border border-zinc-200 bg-zinc-50 ps-9 pe-16 text-sm text-zinc-800 outline-none transition placeholder:text-zinc-400 focus:border-[#006492]/50 focus:bg-white focus:ring-2 focus:ring-[#006492]/15 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:bg-zinc-800" />

            <button x-cloak x-show="searching" x-on:click="clearSearch" type="button"
                class="absolute end-2 top-1/2 inline-flex size-6 -translate-y-1/2 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-white"
                aria-label="Limpiar búsqueda">
                <flux:icon name="x-mark" class="size-3.5" />
            </button>

            <kbd x-show="!searching"
                class="pointer-events-none absolute end-2 top-1/2 -translate-y-1/2 rounded-md border border-zinc-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-zinc-400 shadow-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                Ctrl K
            </kbd>
        </div>

        {{-- Navegación con desplazamiento independiente --}}
        <div class="app-sidebar-scroll min-h-0 flex-1 overflow-y-auto overflow-x-hidden pe-1">
            <div
                class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">
                Plataforma
            </div>

            <flux:sidebar.nav class="space-y-1"
                x-on:click="if ($event.target.closest('a')) query = ''">
                <flux:sidebar.item icon="layout-dashboard" :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')" wire:navigate data-sidebar-search-item
                    data-sidebar-search="inicio principal panel dashboard"
                    x-show="itemMatches($el)">
                    Inicio
                </flux:sidebar.item>

                @if ($isProfessor)
                    <flux:sidebar.item icon="calendar-days" :href="route('docente.horario')"
                        :current="request()->routeIs('docente.horario')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="mi horario clases talleres docente" x-show="itemMatches($el)">
                        Mi horario
                    </flux:sidebar.item>

                    @foreach ($teacherLevels as $teacherLevel)
                        @if ($teacherLevel->slug === 'preescolar')
                            <flux:sidebar.item icon="document-text" :href="route('docente.fichas')"
                                :current="request()->routeIs('docente.fichas')" wire:navigate data-sidebar-search-item
                                data-sidebar-search="fichas descriptivas preescolar" x-show="itemMatches($el)">
                                Fichas descriptivas
                            </flux:sidebar.item>
                        @else
                            <flux:sidebar.item icon="pencil-square" :href="route('docente.calificaciones', $teacherLevel->slug)"
                                :current="request()->routeIs('docente.calificaciones') && request()->route('slug_nivel') === $teacherLevel->slug" wire:navigate data-sidebar-search-item
                                data-sidebar-search="calificaciones {{ $teacherLevel->nombre }} captura" x-show="itemMatches($el)">
                                Calificaciones · {{ $teacherLevel->nombre }}
                            </flux:sidebar.item>
                        @endif
                    @endforeach
                @else

                {{-- Gestión escolar --}}
                <flux:sidebar.group expandable icon="graduation-cap" heading="Gestión escolar"
                    :expanded="$gestionEscolarExpanded"
                    x-bind:open="searching || @js($gestionEscolarExpanded)"
                    x-show="groupMatches($el)">
                    <flux:sidebar.item icon="users" :href="route('misrutas.alumnos')"
                        :current="request()->routeIs('misrutas.alumnos')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="alumnos estudiantes matrícula listado escolar"
                        x-show="itemMatches($el)">
                        Alumnos
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="user-plus" :href="route('misrutas.inscripcion')"
                        :current="request()->routeIs('misrutas.inscripcion')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="inscripción inscripciones registrar nuevo alumno alta"
                        x-show="itemMatches($el)">
                        Inscripción de alumnos
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users-round" :href="route('misrutas.tutores')"
                        :current="request()->routeIs('misrutas.tutores')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="tutores responsables padres familiares alumnos"
                        x-show="itemMatches($el)">
                        Tutores y responsables
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Herramientas académicas --}}
                @if ($canAccessIntegrity || $canAccessTracking || $canAccessAnalytics)
                    <flux:sidebar.group expandable icon="book-open" heading="Académica"
                        :expanded="$academicaExpanded"
                        x-bind:open="searching || @js($academicaExpanded)"
                        x-show="groupMatches($el)">
                        @if ($canAccessIntegrity)
                            <flux:sidebar.item icon="shield-check" :href="route('misrutas.integridad-academica')"
                                :current="request()->routeIs('misrutas.integridad-academica')" wire:navigate
                                :badge="$integridadAbiertos > 0 ? ($integridadAbiertos > 99 ? '99+' : $integridadAbiertos) : null"
                                badge-color="rose" :icon-dot="$integridadAbiertos > 0" data-sidebar-search-item
                                data-sidebar-search="integridad académica casos incidencias correcciones"
                                x-show="itemMatches($el)">
                                Integridad académica
                            </flux:sidebar.item>
                        @endif

                        @if ($canAccessTracking)
                            <flux:sidebar.item icon="chart-bar-square"
                                :href="route('misrutas.seguimiento-academico')"
                                :current="request()->routeIs('misrutas.seguimiento-academico')" wire:navigate
                                :badge="$alertasAcademicas > 0 ? ($alertasAcademicas > 99 ? '99+' : $alertasAcademicas) : null"
                                badge-color="orange" :icon-dot="$alertasAcademicas > 0" data-sidebar-search-item
                                data-sidebar-search="seguimiento académico alertas riesgo intervención alumnos"
                                x-show="itemMatches($el)">
                                Seguimiento académico
                            </flux:sidebar.item>
                        @endif

                        @if ($canAccessAnalytics)
                            <flux:sidebar.item icon="presentation-chart-line"
                                :href="route('misrutas.analitica-institucional')"
                                :current="request()->routeIs('misrutas.analitica-institucional', 'misrutas.analitica.*')"
                                wire:navigate
                                :badge="$alertasDirectivas > 0 ? ($alertasDirectivas > 99 ? '99+' : $alertasDirectivas) : null"
                                badge-color="violet" :icon-dot="$alertasDirectivas > 0" data-sidebar-search-item
                                data-sidebar-search="analítica institucional indicadores estadísticas alertas dirección"
                                x-show="itemMatches($el)">
                                Analítica institucional
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- Estructura institucional --}}
                <flux:sidebar.group expandable icon="school" heading="Estructura escolar"
                    :expanded="$estructuraExpanded"
                    x-bind:open="searching || @js($estructuraExpanded)"
                    x-show="groupMatches($el)">
                    <flux:sidebar.item icon="school" :href="route('misrutas.escuela')"
                        :current="request()->routeIs('misrutas.escuela')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="escuela plantel datos institucionales cct"
                        x-show="itemMatches($el)">
                        Escuela
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="calendar-range" :href="route('misrutas.ciclos')"
                        :current="request()->routeIs('misrutas.ciclos')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="ciclos escolares ciclo escolar fechas"
                        x-show="itemMatches($el)">
                        Ciclos escolares
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="layers" :href="route('misrutas.niveles')"
                        :current="request()->routeIs('misrutas.niveles')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="niveles configuración preescolar primaria secundaria bachillerato"
                        x-show="itemMatches($el)">
                        Niveles y configuración
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="list-ordered" :href="route('misrutas.grados')"
                        :current="request()->routeIs('misrutas.grados')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="grados grado escolar"
                        x-show="itemMatches($el)">
                        Grados
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="history" :href="route('misrutas.generaciones')"
                        :current="request()->routeIs('misrutas.generaciones')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="generaciones generación ciclo ingreso egreso"
                        x-show="itemMatches($el)">
                        Generaciones
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="users" :href="route('misrutas.grupos')"
                        :current="request()->routeIs('misrutas.grupos')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="grupos grupo salón asignación"
                        x-show="itemMatches($el)">
                        Grupos
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="calendar-clock" :href="route('misrutas.periodos')"
                        :current="request()->routeIs('misrutas.periodos')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="periodos evaluación parciales fechas"
                        x-show="itemMatches($el)">
                        Periodos
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="book-open" :href="route('misrutas.materias')"
                        :current="request()->routeIs('misrutas.materias')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="materias asignaturas plan académico"
                        x-show="itemMatches($el)">
                        Materias
                    </flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Personal --}}
                <flux:sidebar.group expandable icon="briefcase" heading="Personal"
                    :expanded="$personalExpanded"
                    x-bind:open="searching || @js($personalExpanded)"
                    x-show="groupMatches($el)">
                    <flux:sidebar.item icon="user-plus" :href="route('misrutas.personal')"
                        :current="request()->routeIs('misrutas.personal')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="crear persona alta personal registro"
                        x-show="itemMatches($el)">
                        Crear persona
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="shield-check" :href="route('misrutas.role-persona')"
                        :current="request()->routeIs('misrutas.role-persona')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="roles permisos personal cargos"
                        x-show="itemMatches($el)">
                        Roles
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="clipboard-list" :href="route('misrutas.plantilla')"
                        :current="request()->routeIs('misrutas.plantilla')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="plantilla personal empleados adscripción"
                        x-show="itemMatches($el)">
                        Plantilla
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="presentation" :href="route('misrutas.profesores')"
                        :current="request()->routeIs('misrutas.profesores')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="profesores docentes maestros horarios materias"
                        x-show="itemMatches($el)">
                        Profesores
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="landmark" :href="route('misrutas.autoridades')"
                        :current="request()->routeIs('misrutas.autoridades')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="autoridades directores supervisores firmas"
                        x-show="itemMatches($el)">
                        Autoridades
                    </flux:sidebar.item>

                    @if ($isAdmin)
                        <flux:sidebar.item icon="briefcase" :href="route('misrutas.expedientes-personal')"
                            :current="request()->routeIs('misrutas.expedientes-personal*')" wire:navigate
                            data-sidebar-search-item
                            data-sidebar-search="expedientes del personal documentos empleados"
                            x-show="itemMatches($el)">
                            Expedientes del personal
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                {{-- Documentación --}}
                @if ($isAdmin)
                    <flux:sidebar.group expandable icon="folder-open" heading="Documentación"
                        :expanded="$documentacionExpanded"
                        x-bind:open="searching || @js($documentacionExpanded)"
                        x-show="groupMatches($el)">
                        <flux:sidebar.item icon="folder-open" :href="route('misrutas.expedientes')"
                            :current="request()->routeIs('misrutas.expedientes', 'misrutas.expedientes.*')"
                            wire:navigate data-sidebar-search-item
                            data-sidebar-search="expedientes digitales documentación alumnos archivos"
                            x-show="itemMatches($el)">
                            Expedientes digitales
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="file-check" :href="route('misrutas.constancias')"
                            :current="request()->routeIs('misrutas.constancias*')" wire:navigate
                            data-sidebar-search-item
                            data-sidebar-search="constancias estudios conducta traslado documentos"
                            x-show="itemMatches($el)">
                            Constancias
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="scroll-text" :href="route('misrutas.oficios')"
                            :current="request()->routeIs('misrutas.oficios*')" wire:navigate data-sidebar-search-item
                            data-sidebar-search="oficios documentos administrativos invitaciones"
                            x-show="itemMatches($el)">
                            Oficios
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                {{-- Media superior --}}
                <flux:sidebar.group expandable icon="graduation-cap" heading="Media superior"
                    :expanded="$mediaSuperiorExpanded"
                    x-bind:open="searching || @js($mediaSuperiorExpanded)"
                    x-show="groupMatches($el)">
                    <flux:sidebar.item icon="calendar-days" :href="route('misrutas.semestres')"
                        :current="request()->routeIs('misrutas.semestres')" wire:navigate data-sidebar-search-item
                        data-sidebar-search="semestres bachillerato media superior"
                        x-show="itemMatches($el)">
                        Semestres
                    </flux:sidebar.item>

                    @if ($isAdmin)
                        <flux:sidebar.item icon="document-text" :href="route('media-superior.documentos.index')"
                            :current="request()->routeIs('media-superior.documentos.index', 'media-superior.documentos.modulo')"
                            wire:navigate data-sidebar-search-item
                            data-sidebar-search="documentos oficiales bachillerato media superior"
                            x-show="itemMatches($el)">
                            Documentos oficiales
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="cog-6-tooth"
                            :href="route('media-superior.documentos.configuracion')"
                            :current="request()->routeIs('media-superior.documentos.configuracion')" wire:navigate
                            data-sidebar-search-item
                            data-sidebar-search="configuración documental bachillerato media superior"
                            x-show="itemMatches($el)">
                            Configuración documental
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                {{-- Administración --}}
                @if ($canAccessAdministration)
                    <flux:sidebar.group expandable icon="settings" heading="Administración"
                        :expanded="$administracionExpanded"
                        x-bind:open="searching || @js($administracionExpanded)"
                        x-show="groupMatches($el)">
                        <flux:sidebar.item icon="shield-check" :href="route('misrutas.centro-control')"
                            :current="request()->routeIs('misrutas.centro-control')" wire:navigate
                            :badge="$systemUnread > 0 ? ($systemUnread > 99 ? '99+' : $systemUnread) : null"
                            badge-color="red" :icon-dot="$systemUnread > 0" data-sidebar-search-item
                            data-sidebar-search="centro de control administración sistema notificaciones"
                            x-show="itemMatches($el)">
                            Centro de control
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="database-backup"
                            :href="route('misrutas.respaldos-academicos')"
                            :current="request()->routeIs('misrutas.respaldos-academicos')" wire:navigate
                            data-sidebar-search-item
                            data-sidebar-search="respaldos académicos importar exportar base de datos"
                            x-show="itemMatches($el)">
                            Respaldos académicos
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                {{-- Accesos operativos por nivel --}}
                <livewire:nav-niveles />
                @endif
            </flux:sidebar.nav>

            <div x-cloak x-show="searching && !hasResults()"
                class="mx-1 mt-4 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-3 py-5 text-center dark:border-zinc-700 dark:bg-zinc-800/60 in-data-flux-sidebar-collapsed-desktop:hidden">
                <flux:icon name="magnifying-glass" class="mx-auto mb-2 size-5 text-zinc-400" />
                <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Sin resultados</p>
                <p class="mt-1 text-[11px] leading-4 text-zinc-400">Prueba con alumno, horario, tutor o ciclo.</p>
            </div>
        </div>

        {{-- Perfil al pie del menú --}}
        <div class="border-t border-zinc-200 pt-2 dark:border-zinc-700">
            <flux:dropdown position="top" align="start" class="w-full">
                <button type="button"
                    class="flex h-11 w-full min-w-0 items-center gap-2 rounded-xl px-1.5 text-start transition hover:bg-zinc-100 dark:hover:bg-white/5 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-0"
                    aria-label="Abrir menú de usuario">
                    <span class="relative flex size-8 shrink-0 overflow-hidden rounded-lg">
                        @if ($user?->photo)
                            <img src="{{ asset('storage/profile-photos/' . $user->photo) }}" alt="{{ $user->name }}"
                                class="size-full object-cover">
                        @else
                            <span
                                class="flex size-full items-center justify-center rounded-lg bg-zinc-800 text-xs font-bold text-white dark:bg-zinc-700">
                                {{ $user?->initials() ?? '?' }}
                            </span>
                        @endif
                    </span>

                    <span class="min-w-0 flex-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                        <span class="block truncate text-xs font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $user?->name }}
                        </span>
                        <span class="block truncate text-[10px] text-zinc-400">
                            {{ $user?->email }}
                        </span>
                    </span>

                    <flux:icon name="chevrons-up-down"
                        class="size-4 shrink-0 text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden" />
                </button>

                <flux:menu class="w-[240px]">
                    <div class="px-2 py-2">
                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $user?->name }}</p>
                        <p class="truncate text-xs text-zinc-400">{{ $user?->roleLabel() }}</p>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile.edit')" icon="settings" wire:navigate>
                        Configuración
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="log-out" class="w-full">
                            Cerrar sesión
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:sidebar>

    {{-- Encabezado móvil --}}
    <flux:header class="border-b border-zinc-200 bg-white lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Abrir menú" />

        <a href="{{ route('dashboard') }}" class="ms-2 flex items-center gap-2" wire:navigate>
            <span class="relative flex size-8 items-center justify-center overflow-hidden rounded-lg bg-[#006492] text-xs font-black text-white">
                M
                <span class="absolute inset-x-0 bottom-0 h-0.5 bg-[#88AC2E]"></span>
            </span>
            <span class="text-sm font-bold text-zinc-900 dark:text-white">Moctezuma</span>
        </a>

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="$user?->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <div class="px-2 py-2">
                    <p class="truncate text-sm font-semibold">{{ $user?->name }}</p>
                    <p class="truncate text-xs text-zinc-400">{{ $user?->email }}</p>
                </div>

                <flux:menu.separator />

                <flux:menu.item :href="route('profile.edit')" icon="settings" wire:navigate>
                    Configuración
                </flux:menu.item>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="log-out" class="w-full">
                        Cerrar sesión
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
    @stack('scripts')
</body>

</html>
