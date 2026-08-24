<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')

    <style>
        :root {
            --cum-blue: #006492;
            --cum-blue-deep: #034f73;
            --cum-green: #88AC2E;
            --cum-ink: #0f172a;
            --cum-muted: #64748b;
        }

        .auth-shell {
            min-height: 100svh;
            background:
                radial-gradient(circle at 78% 14%, rgba(136, 172, 46, .10), transparent 28rem),
                radial-gradient(circle at 72% 82%, rgba(0, 100, 146, .08), transparent 30rem),
                #f8fafc;
        }

        .auth-hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background:
                linear-gradient(145deg, rgba(0, 100, 146, .98) 0%, rgba(3, 79, 115, .97) 46%, rgba(10, 91, 111, .95) 100%);
        }

        .auth-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -3;
            opacity: .32;
            background-image:
                linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.9), transparent 88%);
        }

        .auth-hero::after {
            content: '';
            position: absolute;
            width: 42rem;
            height: 42rem;
            right: -17rem;
            bottom: -19rem;
            z-index: -2;
            border-radius: 9999px;
            background: radial-gradient(circle at 38% 35%, rgba(190, 225, 100, .55), rgba(136, 172, 46, .16) 38%, transparent 70%);
            filter: blur(2px);
        }

        .auth-orb {
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
            filter: blur(.2px);
        }

        .auth-orb-one {
            width: 23rem;
            height: 23rem;
            top: -8rem;
            left: -8rem;
            background: radial-gradient(circle at 70% 70%, rgba(255,255,255,.19), rgba(255,255,255,.02) 62%, transparent 70%);
            border: 1px solid rgba(255,255,255,.13);
        }

        .auth-orb-two {
            width: 16rem;
            height: 16rem;
            top: 28%;
            right: -6rem;
            background: rgba(255,255,255,.055);
            border: 1px solid rgba(255,255,255,.10);
        }

        .auth-glow-line {
            position: absolute;
            left: 8%;
            right: 8%;
            bottom: 18%;
            height: 1px;
            opacity: .5;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.65), transparent);
        }

        .auth-panel-card {
            border: 1px solid rgba(148, 163, 184, .20);
            background: rgba(255, 255, 255, .88);
            box-shadow:
                0 24px 70px rgba(15, 23, 42, .10),
                0 2px 8px rgba(15, 23, 42, .04);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .auth-brand-mark {
            box-shadow: 0 12px 34px rgba(0, 100, 146, .18);
        }

        .auth-feature-card {
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.07);
            backdrop-filter: blur(10px);
        }

        .auth-status-dot {
            box-shadow: 0 0 0 5px rgba(136, 172, 46, .14);
        }

        .auth-panel-card [data-flux-control] {
            min-height: 3rem;
            border-radius: .85rem !important;
            border-color: #dbe4ee !important;
            background-color: rgba(248, 250, 252, .78) !important;
            transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .auth-panel-card [data-flux-control]:focus {
            border-color: rgba(0, 100, 146, .65) !important;
            background-color: #fff !important;
            box-shadow: 0 0 0 4px rgba(0, 100, 146, .10) !important;
        }

        .auth-panel-card [data-flux-label] {
            color: #334155 !important;
            font-size: .84rem !important;
            font-weight: 650 !important;
        }

        .auth-login-button {
            min-height: 3.15rem !important;
            border: 0 !important;
            border-radius: .9rem !important;
            background: linear-gradient(135deg, var(--cum-blue) 0%, #087b9a 100%) !important;
            box-shadow: 0 12px 26px rgba(0, 100, 146, .22) !important;
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease !important;
        }

        .auth-login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(0, 100, 146, .28) !important;
            filter: saturate(1.06);
        }

        .auth-login-button:active {
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: no-preference) {
            .auth-float {
                animation: auth-float 7s ease-in-out infinite;
            }

            @keyframes auth-float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-8px); }
            }
        }

        @media (max-width: 1023px) {
            .auth-shell {
                background:
                    radial-gradient(circle at 18% 4%, rgba(0, 100, 146, .13), transparent 19rem),
                    radial-gradient(circle at 92% 93%, rgba(136, 172, 46, .12), transparent 18rem),
                    #f8fafc;
            }
        }
    </style>
</head>

<body class="min-h-screen antialiased text-slate-900">
    <main class="auth-shell grid min-h-svh lg:grid-cols-[1.08fr_.92fr]">
        <section class="auth-hero relative hidden min-h-svh flex-col justify-between px-10 py-9 text-white lg:flex xl:px-14 xl:py-12">
            <span class="auth-orb auth-orb-one"></span>
            <span class="auth-orb auth-orb-two"></span>
            <span class="auth-glow-line"></span>

            <div class="relative z-10 flex items-center justify-between">
                <a href="{{ route('home') }}" class="inline-flex items-center" wire:navigate aria-label="Ir al inicio">
                    <span class="rounded-2xl bg-white/95 px-4 py-3 shadow-lg shadow-slate-950/10 ring-1 ring-white/30">
                        <img src="{{ asset('imagenes/logo-oficial-cum.png') }}" alt="Centro Universitario Moctezuma" class="h-12 w-auto object-contain xl:h-14" />
                    </span>
                </a>

                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-xs font-medium text-white/90 backdrop-blur-md">
                    <span class="auth-status-dot size-2 rounded-full bg-[#a9cf45]"></span>
                    Plataforma institucional
                </div>
            </div>

            <div class="relative z-10 max-w-2xl pb-8 xl:pb-14">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-wide text-white/90 backdrop-blur-md">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 2.8 8.2 12 13.4l9.2-5.2L12 3Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 10.5v5.2c0 1.7 2.5 3.1 5.5 3.1s5.5-1.4 5.5-3.1v-5.2" />
                    </svg>
                    Moctezuma Básica
                </div>

                <h1 class="max-w-xl text-4xl font-semibold leading-[1.08] tracking-[-.035em] text-white xl:text-[3.35rem]">
                    Gestión escolar clara, moderna y en un solo lugar.
                </h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-sky-50/80 xl:text-[1.05rem]">
                    Administra alumnos, docentes, horarios, calificaciones y documentación académica desde una experiencia centralizada.
                </p>

                <div class="mt-9 grid max-w-2xl grid-cols-3 gap-3">
                    <div class="auth-feature-card rounded-2xl p-4">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-xl bg-white/12 text-[#c6e777]">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white">Control escolar</p>
                        <p class="mt-1 text-xs leading-5 text-sky-100/65">Matrícula y seguimiento</p>
                    </div>

                    <div class="auth-feature-card rounded-2xl p-4">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-xl bg-white/12 text-[#c6e777]">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="17" rx="2" />
                                <path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white">Horarios</p>
                        <p class="mt-1 text-xs leading-5 text-sky-100/65">Organización académica</p>
                    </div>

                    <div class="auth-feature-card rounded-2xl p-4">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-xl bg-white/12 text-[#c6e777]">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
                                <path stroke-linecap="round" d="M9 7h7M9 11h5" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white">Resultados</p>
                        <p class="mt-1 text-xs leading-5 text-sky-100/65">Calificaciones y reportes</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex items-center justify-between border-t border-white/10 pt-5 text-xs text-sky-100/60">
                <span>Centro Universitario Moctezuma</span>
                <span>Educación básica</span>
            </div>
        </section>

        <section class="relative flex min-h-svh items-center justify-center px-5 py-8 sm:px-8 lg:px-10 xl:px-16">
            <div class="w-full max-w-[30rem]">
                <div class="mb-7 flex items-center justify-between lg:hidden">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3" wire:navigate>
                        <span class="auth-brand-mark flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#087b9a] text-white">
                            <img src="{{ asset('letra.png') }}" alt="Moctezuma" class="size-7 object-contain" />
                        </span>
                        <span>
                            <span class="block text-sm font-bold tracking-tight text-slate-900">Moctezuma Básica</span>
                            <span class="block text-xs text-slate-500">Plataforma institucional</span>
                        </span>
                    </a>
                    <span class="auth-status-dot size-2.5 rounded-full bg-[#88AC2E]"></span>
                </div>

                <div class="auth-panel-card rounded-[1.75rem] p-6 sm:p-8 lg:p-9">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                    Acceso exclusivo para personal autorizado del Centro Universitario Moctezuma.
                </p>
            </div>
        </section>
    </main>

    @fluxScripts
</body>

</html>
