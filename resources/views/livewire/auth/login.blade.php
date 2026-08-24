<x-layouts.auth>
    <div class="flex flex-col">
        <div class="mb-7">
            <div class="mb-4 inline-flex size-11 items-center justify-center rounded-2xl bg-[#006492]/10 text-[#006492] ring-1 ring-[#006492]/10">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="10" rx="2" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0 1 10 0v4" />
                    <path stroke-linecap="round" d="M12 15v2" />
                </svg>
            </div>

            <p class="text-xs font-semibold uppercase tracking-[.16em] text-[#006492]">Bienvenido de nuevo</p>
            <h1 class="mt-2 text-[1.75rem] font-semibold leading-tight tracking-[-.025em] text-slate-950 sm:text-3xl">
                Inicia sesión en tu cuenta
            </h1>
            <p class="mt-2.5 max-w-md text-sm leading-6 text-slate-500">
                Ingresa tus credenciales para acceder al panel administrativo de Moctezuma Básica.
            </p>
        </div>

        <x-auth-session-status class="mb-5 text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                label="Correo electrónico"
                :value="old('email')"
                type="email"
                autofocus
                autocomplete="email"
                placeholder="nombre@moctezuma.edu.mx"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    label="Contraseña"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link
                        class="absolute top-0 text-xs font-semibold text-[#006492] hover:text-[#034f73] end-0"
                        :href="route('password.request')"
                        wire:navigate
                    >
                        ¿Olvidaste tu contraseña?
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center justify-between gap-4 py-0.5">
                <flux:checkbox name="remember" label="Mantener sesión activa" :checked="old('remember')" />

                <span class="hidden items-center gap-1.5 text-xs font-medium text-slate-400 sm:inline-flex">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
                    </svg>
                    Acceso seguro
                </span>
            </div>

            <flux:button
                variant="primary"
                type="submit"
                class="auth-login-button w-full font-semibold"
                data-test="login-button"
            >
                <span class="inline-flex items-center justify-center gap-2">
                    Iniciar sesión
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </span>
            </flux:button>
        </form>

        <div class="mt-6 flex items-center gap-3 text-[11px] uppercase tracking-[.12em] text-slate-300">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="font-semibold text-slate-400">Sistema institucional</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>

        <div class="mt-5 flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/70 px-4 py-3.5">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#88AC2E]/12 text-[#6f9020]">
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path stroke-linecap="round" d="M12 10v5M12 7h.01" />
                </svg>
            </span>
            <p class="text-xs leading-5 text-slate-500">
                Si tienes problemas para ingresar, contacta al administrador del sistema.
            </p>
        </div>

        @if (Route::has('register'))
            <div class="mt-5 text-center text-sm text-slate-500">
                <span>¿No tienes una cuenta?</span>
                <flux:link class="font-semibold text-[#006492]" :href="route('register')" wire:navigate>Crear cuenta</flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
