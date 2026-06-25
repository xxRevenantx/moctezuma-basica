<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')

    <x-head.tinymce-config />
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">

    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        {{-- Toggle mobile --}}
        <flux:sidebar.toggle class="mb-2 lg:hidden" icon="x-mark" />

        {{-- Marca superior --}}
        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 px-1 rtl:space-x-reverse"
            wire:navigate>
            <x-app-logo />
        </a>

        {{-- Tarjeta principal --}}
        <div
            class="mt-4 flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-3.5 py-3 shadow-[0_0_0_1px_rgba(255,255,255,0.02)] dark:border-zinc-700 dark:bg-zinc-900">
            {{-- Encabezado usuario --}}
            <div class="flex items-center justify-between gap-2">
                <livewire:ImageProfile.image-profile />

                <flux:dropdown class="hidden lg:inline-flex" position="bottom" align="end">
                    <button type="button" aria-label="Abrir menú de usuario"
                        class="inline-flex size-7 items-center justify-center rounded-lg bg-indigo-500 text-white transition hover:bg-indigo-600">
                        <flux:icon name="chevrons-up-down" class="size-4" />
                    </button>

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex size-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex size-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            @auth
                                                {{ auth()->user()->initials() }}
                                            @endauth
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        @auth
                                            <span class="truncate font-semibold">
                                                {{ auth()->user()->name }}
                                            </span>

                                            <span class="truncate text-xs">
                                                {{ auth()->user()->email }}
                                            </span>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="settings" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf

                            <flux:menu.item as="button" type="submit" icon="log-out" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>

            {{-- Botón Nuevo Alumno --}}
            <a href="{{ route('misrutas.inscripcion') }}"
                class="mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-500 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-zinc-950"
                wire:navigate>
                <flux:icon name="user-plus" class="size-4" />

                <span>Nuevo Alumno</span>
            </a>

            {{-- Navegación principal --}}
            <flux:navlist class="mt-1 space-y-1 [&>div]:border-0 [&>div]:p-0">
                <flux:navlist.group :heading="__('Plataforma')" class="grid gap-1 text-[13px] text-zinc-300">
                    {{-- Inicio --}}
                    <flux:navlist.item icon="layout-dashboard" :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')" wire:navigate>
                        Inicio
                    </flux:navlist.item>

                    {{-- Documentación: acceso exclusivo para administración --}}
                    @if (auth()->user()?->is_admin)
                        <flux:sidebar.group expandable :expanded="request()->routeIs('misrutas.expedientes*', 'misrutas.constancias', 'misrutas.oficios')" heading="DOCUMENTACIÓN"
                            class="grid gap-1 text-xs text-zinc-300">
                            <flux:navlist.item icon="folder-open" :href="route('misrutas.expedientes')"
                                :current="request()->routeIs('misrutas.expedientes*')" wire:navigate>
                                Expedientes digitales
                            </flux:navlist.item>

                            <flux:navlist.item icon="file-check" :href="route('misrutas.constancias')"
                                :current="request()->routeIs('misrutas.constancias')" wire:navigate>
                                Constancias
                            </flux:navlist.item>

                            <flux:navlist.item icon="scroll-text" :href="route('misrutas.oficios')"
                                :current="request()->routeIs('misrutas.oficios')" wire:navigate>
                                Oficios
                            </flux:navlist.item>
                        </flux:sidebar.group>
                    @endif

                    {{-- Académica --}}
                    <flux:sidebar.group expandable :expanded="request()->routeIs('misrutas.alumnos', 'misrutas.escuela', 'misrutas.ciclos', 'misrutas.tutores', 'misrutas.autoridades', 'misrutas.niveles', 'misrutas.respaldos-academicos')" heading="ACADÉMICA"
                        class="grid gap-1 text-xs text-zinc-300">
                        <flux:navlist.item icon="graduation-cap" :href="route('misrutas.alumnos')"
                            :current="request()->routeIs('misrutas.alumnos')" wire:navigate>
                            Alumnos
                        </flux:navlist.item>

                        @if (auth()->user()?->is_admin)
                            <flux:navlist.item icon="database-backup" :href="route('misrutas.respaldos-academicos')"
                                :current="request()->routeIs('misrutas.respaldos-academicos')" wire:navigate>
                                Respaldos académicos
                            </flux:navlist.item>
                        @endif

                        <flux:navlist.item icon="school" :href="route('misrutas.escuela')"
                            :current="request()->routeIs('misrutas.escuela')" wire:navigate>
                            Escuela
                        </flux:navlist.item>

                        <flux:navlist.item icon="calendar-range" :href="route('misrutas.ciclos')"
                            :current="request()->routeIs('misrutas.ciclos')" wire:navigate>
                            Ciclos Escolares
                        </flux:navlist.item>

                        <flux:navlist.item icon="users-round" :href="route('misrutas.tutores')"
                            :current="request()->routeIs('misrutas.tutores')" wire:navigate>
                            Tutores
                        </flux:navlist.item>

                        <flux:navlist.item icon="landmark" :href="route('misrutas.autoridades')"
                            :current="request()->routeIs('misrutas.autoridades')" wire:navigate>
                            Autoridades
                        </flux:navlist.item>

                        <flux:navlist.item icon="layers" :href="route('misrutas.niveles')"
                            :current="request()->routeIs('misrutas.niveles')" wire:navigate>
                            Niveles
                        </flux:navlist.item>
                    </flux:sidebar.group>

                    {{-- Personal --}}
                    <flux:sidebar.group expandable
                        :expanded="request()->routeIs('misrutas.personal', 'misrutas.role-persona', 'misrutas.plantilla', 'misrutas.profesores', 'misrutas.expedientes-personal*')"
                        heading="PERSONAL" class="grid gap-1 text-xs text-zinc-300">
                        <flux:navlist.item icon="user-plus" :href="route('misrutas.personal')"
                            :current="request()->routeIs('misrutas.personal')" wire:navigate>
                            Crear Persona
                        </flux:navlist.item>

                        <flux:navlist.item icon="shield-check" :href="route('misrutas.role-persona')"
                            :current="request()->routeIs('misrutas.role-persona')" wire:navigate>
                            Roles
                        </flux:navlist.item>

                        <flux:navlist.item icon="clipboard-list" :href="route('misrutas.plantilla')"
                            :current="request()->routeIs('misrutas.plantilla')" wire:navigate>
                            Plantilla
                        </flux:navlist.item>

                        <flux:navlist.item icon="presentation" :href="route('misrutas.profesores')"
                            :current="request()->routeIs('misrutas.profesores')" wire:navigate>
                            Profesores
                        </flux:navlist.item>

                        @if (auth()->user()?->is_admin)
                            <flux:navlist.item icon="briefcase" :href="route('misrutas.expedientes-personal')"
                                :current="request()->routeIs('misrutas.expedientes-personal*')" wire:navigate>
                                Expedientes del personal
                            </flux:navlist.item>
                        @endif
                    </flux:sidebar.group>

                    {{-- Estructura --}}
                    <flux:sidebar.group expandable :expanded="false" heading="ESTRUCTURA"
                        class="grid gap-1 text-xs text-zinc-300">
                        <flux:navlist.item icon="list-ordered" :href="route('misrutas.grados')"
                            :current="request()->routeIs('misrutas.grados')" wire:navigate>
                            Grados
                        </flux:navlist.item>

                        <flux:navlist.item icon="history" :href="route('misrutas.generaciones')"
                            :current="request()->routeIs('misrutas.generaciones')" wire:navigate>
                            Generaciones
                        </flux:navlist.item>

                        <flux:navlist.item icon="users" :href="route('misrutas.grupos')"
                            :current="request()->routeIs('misrutas.grupos')" wire:navigate>
                            Grupos
                        </flux:navlist.item>

                        <flux:navlist.item icon="calendar-clock" :href="route('misrutas.periodos')"
                            :current="request()->routeIs('misrutas.periodos')" wire:navigate>
                            Periodos
                        </flux:navlist.item>

                        <flux:navlist.item icon="book-open" :href="route('misrutas.materias')"
                            :current="request()->routeIs('misrutas.materias')" wire:navigate>
                            Materias
                        </flux:navlist.item>
                    </flux:sidebar.group>

                    {{-- Media superior --}}
                    <flux:sidebar.group expandable :expanded="false" heading="MEDIA SUPERIOR"
                        class="grid gap-1 text-xs text-zinc-300">
                        <flux:navlist.item icon="calendar-days" :href="route('misrutas.semestres')"
                            :current="request()->routeIs('misrutas.semestres')" wire:navigate>
                            Semestres
                        </flux:navlist.item>
                    </flux:sidebar.group>
                </flux:navlist.group>

                <livewire:nav-niveles />
            </flux:navlist>
        </div>

        <flux:spacer />
    </flux:sidebar>

    {{-- Menú móvil --}}
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex size-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex size-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">
                                    {{ auth()->user()->name }}
                                </span>

                                <span class="truncate text-xs">
                                    {{ auth()->user()->email }}
                                </span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="settings" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf

                    <flux:menu.item as="button" type="submit" icon="log-out" class="w-full">
                        {{ __('Log Out') }}
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
