<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 ">
        {{-- Toggle mobile --}}
        <flux:sidebar.toggle class="lg:hidden mb-2" icon="x-mark" />

        {{-- Marca superior --}}
        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse px-1"
            wire:navigate>
            <x-app-logo />
        </a>

        {{-- Tarjeta principal tipo Lucid --}}
        <div
            class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900  px-3.5 py-3 flex flex-col gap-3 shadow-[0_0_0_1px_rgba(255,255,255,0.02)]">
            {{-- Encabezado usuario --}}
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-lg">
                        <span
                            class="flex h-full w-full items-center justify-center rounded-lg bg-zinc-800 text-sm font-semibold text-zinc-50">
                            {{ auth()->user()->initials() }}
                        </span>
                    </span>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold truncate">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[11px] text-zinc-400">
                            Personal
                        </span>
                    </div>
                </div>

                {{-- mini menú (solo icono, el dropdown completo queda abajo) --}}
                <flux:dropdown class="hidden lg:inline-flex" position="bottom" align="end">
                    <button type="button"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-zinc-800 text-zinc-300 hover:bg-zinc-700">
                        <x-flux::icon name="chevrons-up-down" class="w-4 h-4" />
                    </button>

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>

            {{-- Botón + Nuevo --}}
            <a href="#"
                class="mt-2 inline-flex items-center justify-center rounded-xl bg-indigo-500 px-3.5 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-zinc-950">
                <span class="text-base leading-none me-1">+</span>
                <span>Nuevo Alumno</span>
            </a>

            {{-- Navegación principal dentro de la tarjeta --}}
            <flux:navlist {{-- variant="ghost" --}} class="mt-1 space-y-1 [&>div]:border-0 [&>div]:p-0">
                <flux:navlist.group :heading="__('Plataforma')" class="grid gap-1 text-[13px] text-zinc-300">
                    <flux:navlist.item icon="home" :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')" wire:navigate>
                        Inicio
                    </flux:navlist.item>
                    <flux:navlist.item icon="home" :href="route('misrutas.escuela')"
                        :current="request()->routeIs('misrutas.escuela')" wire:navigate>
                        Escuela
                    </flux:navlist.item>
                    <flux:navlist.item icon="home" :href="route('misrutas.directivos')"
                        :current="request()->routeIs('misrutas.directivos')" wire:navigate>
                        Directivos
                    </flux:navlist.item>
                    <flux:navlist.item icon="home" :href="route('misrutas.niveles')"
                        :current="request()->routeIs('misrutas.niveles')" wire:navigate>
                        Niveles
                    </flux:navlist.item>



                </flux:navlist.group>
            </flux:navlist>
        </div>

        {{-- Espacio flexible --}}
        <flux:spacer />


    </flux:sidebar>


    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
