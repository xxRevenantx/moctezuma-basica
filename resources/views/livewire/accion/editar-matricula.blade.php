<div x-data="{ cargandoPagina: true }" x-init="setTimeout(() => cargandoPagina = false, 700)" class="space-y-6">

    {{-- LOADER AL ENTRAR A EDITAR MATRÍCULA --}}
    <div x-cloak x-show="cargandoPagina" x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/80 backdrop-blur-md dark:bg-neutral-950/80">
        <div
            class="mx-4 w-full max-w-sm rounded-[28px] border border-indigo-100 bg-white/95 p-7 text-center shadow-2xl shadow-indigo-500/20 dark:border-indigo-900/40 dark:bg-neutral-900/95">

            <div class="relative mx-auto mb-5 flex h-20 w-20 items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-indigo-100 dark:border-indigo-900/40"></div>
                <div
                    class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-indigo-500 border-r-sky-500">
                </div>

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-600 to-sky-500 text-white shadow-lg shadow-indigo-500/30">
                    <flux:icon.user class="h-5 w-5" />
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                Cargando matrícula
            </h3>

            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                Consultando información del alumno...
            </p>

            <div class="mt-5 flex items-center justify-center gap-1.5">
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-indigo-500 [animation-delay:-0.3s]"></span>
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-blue-500 [animation-delay:-0.15s]"></span>
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-sky-500"></span>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="actualizarInscripcion" class="space-y-6">
        <div
            class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">

            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

            <div class="p-5 sm:p-6 lg:p-8">

                {{-- ENCABEZADO PRO --}}
                @php
                    $nombreAlumnoEditando = trim(
                        ($nombre ?? '') . ' ' . ($apellido_paterno ?? '') . ' ' . ($apellido_materno ?? ''),
                    );

                    $inicialesAlumno = collect(explode(' ', $nombreAlumnoEditando))
                        ->filter()
                        ->take(2)
                        ->map(fn($parte) => mb_substr($parte, 0, 1))
                        ->implode('');

                    $nombreAlumnoEditando =
                        $nombreAlumnoEditando !== '' ? $nombreAlumnoEditando : 'Alumno sin nombre cargado';
                @endphp

                <div
                    class="mb-8 overflow-hidden rounded-[30px] border border-slate-200/70 bg-gradient-to-br from-slate-50 via-white to-sky-50 shadow-xl shadow-slate-200/60 dark:border-white/10 dark:from-neutral-900 dark:via-neutral-900 dark:to-sky-950/20 dark:shadow-black/20">

                    <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600"></div>

                    <div class="relative p-5 sm:p-6 lg:p-7">
                        <div
                            class="pointer-events-none absolute -right-20 -top-20 h-48 w-48 rounded-full bg-sky-400/10 blur-3xl">
                        </div>

                        <div
                            class="pointer-events-none absolute -bottom-24 -left-16 h-52 w-52 rounded-full bg-indigo-400/10 blur-3xl">
                        </div>

                        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">

                            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">

                                {{-- AVATAR --}}
                                <div
                                    class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-[26px] bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-2xl font-black uppercase text-white shadow-xl shadow-sky-500/30">

                                    {{ $inicialesAlumno ?: 'AL' }}

                                    @if ($activo)
                                        <span
                                            class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-emerald-500 dark:border-neutral-900">
                                            <flux:icon.check class="h-3.5 w-3.5 text-white" />
                                        </span>
                                    @else
                                        <span
                                            class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-rose-500 dark:border-neutral-900">
                                            <flux:icon.x-mark class="h-3.5 w-3.5 text-white" />
                                        </span>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                            <flux:icon.pencil-square class="h-3.5 w-3.5" />
                                            Editando matrícula
                                        </span>

                                        @if ($activo)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                Inactivo
                                            </span>
                                        @endif

                                        @if ($esBachillerato)
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                                <flux:icon.academic-cap class="h-3.5 w-3.5" />
                                                Bachillerato
                                            </span>
                                        @endif
                                    </div>

                                    <h1
                                        class="truncate text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                                        {{ $nombreAlumnoEditando }}
                                    </h1>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Actualiza los datos personales, tutor, domicilio y asignación escolar del
                                        alumno.
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm dark:border-neutral-700 dark:bg-neutral-800/80 dark:text-slate-300">
                                            <flux:icon.identification class="h-4 w-4 text-sky-600 dark:text-sky-400" />
                                            <span>Matrícula:</span>
                                            <span class="font-bold text-slate-900 dark:text-white">
                                                {{ $matricula ?: 'Sin matrícula' }}
                                            </span>
                                        </div>

                                        <div
                                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm dark:border-neutral-700 dark:bg-neutral-800/80 dark:text-slate-300">
                                            <flux:icon.calendar-days
                                                class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                            <span>Inscripción:</span>
                                            <span class="font-bold text-slate-900 dark:text-white">
                                                {{ $fecha_inscripcion ?: 'Sin fecha' }}
                                            </span>
                                        </div>

                                        <div
                                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm dark:border-neutral-700 dark:bg-neutral-800/80 dark:text-slate-300">
                                            <flux:icon.shield-check
                                                class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                            <span>Registro seguro:</span>
                                            <span class="font-bold text-slate-900 dark:text-white">
                                                Edición protegida
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ACCIONES --}}
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center xl:justify-end">
                                <span
                                    class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-xs font-bold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    Matrícula protegida
                                </span>

                                <flux:button type="button" variant="ghost" x-data
                                    x-on:click="
                                        const url = localStorage.getItem('matricula_return_url');

                                        if (url) {
                                            window.location.href = url;
                                        } else {
                                            window.history.back();
                                        }
                                    "
                                    class="group inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 hover:shadow-lg hover:shadow-sky-500/10 dark:border-neutral-700 dark:bg-neutral-800/90 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-sky-950/30 dark:hover:text-sky-300">

                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition group-hover:bg-sky-100 group-hover:text-sky-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-sky-950/50 dark:group-hover:text-sky-300">
                                        <flux:icon.arrow-left class="h-4 w-4" />
                                    </span>

                                    <span>Regresar</span>
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>

                @if (!empty($curpSuccess ?? null))
                    <div x-data="{ mostrar: true }" x-init="setTimeout(() => {
                        mostrar = false
                    
                        setTimeout(() => {
                            $wire.dispatch('limpiar-curp-success')
                        }, 500)
                    }, 2000)" x-show="mostrar"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-500"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <p class="font-semibold">CURP encontrada</p>
                        <p class="mt-1">{{ $curpSuccess ?? '' }}</p>
                    </div>
                @endif

                {{-- DATOS PERSONALES --}}
                <section class="space-y-5">
                    <div
                        class="flex flex-col gap-4 rounded-[26px] border border-slate-200 bg-slate-50/80 p-4 dark:border-neutral-800 dark:bg-neutral-900/60 sm:flex-row sm:items-center sm:justify-between">

                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 shadow-sm dark:bg-sky-950/40 dark:text-sky-300">
                                <flux:icon.user class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Datos personales
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Información básica del alumno.
                                </p>
                            </div>
                        </div>


                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>CURP</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:input wire:model.live.debounce.500ms="curp" maxlength="18"
                                placeholder="Ingresa la CURP" />

                            @if (!empty($curpAdvertencia ?? null))
                                <div
                                    class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                    <p class="font-semibold">Advertencia sobre la CURP</p>
                                    <p class="mt-1">{{ $curpAdvertencia ?? '' }}</p>
                                </div>
                            @endif

                            @if (!empty($curpError ?? null))
                                <div
                                    class="mt-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                                    <p class="font-semibold">Error en la CURP</p>
                                    <p class="mt-1">{{ $curpError ?? '' }}</p>
                                </div>
                            @endif

                            @error('curp')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Matrícula</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    Automático
                                </span>
                            </div>

                            <flux:input wire:model="matricula" variant="filled" readonly
                                placeholder="Se genera automáticamente" />

                            @error('matricula')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Folio</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="folio" placeholder="Opcional" />

                            @error('folio')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Nombre(s)</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:input wire:model="nombre" placeholder="Nombre(s)" />

                            @error('nombre')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Apellido paterno</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:input wire:model="apellido_paterno" placeholder="Apellido paterno" />

                            @error('apellido_paterno')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Apellido materno</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="apellido_materno" placeholder="Apellido materno" />

                            @error('apellido_materno')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Fecha de nacimiento</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:input type="date" wire:model="fecha_nacimiento" />

                            @error('fecha_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Género</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:select wire:model="genero">
                                <flux:select.option value="">Selecciona una opción</flux:select.option>
                                <flux:select.option value="H">Hombre</flux:select.option>
                                <flux:select.option value="M">Mujer</flux:select.option>
                            </flux:select>

                            @error('genero')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Fecha de inscripción</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:input type="date" wire:model="fecha_inscripcion" />

                            @error('fecha_inscripcion')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Periodo de inscripción</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:select wire:model="ciclo_id">
                                <flux:select.option value="">Selecciona un ciclo</flux:select.option>

                                @foreach ($ciclos as $ciclo)
                                    <flux:select.option value="{{ $ciclo->id }}">
                                        {{ $ciclo->ciclo }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('ciclo_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- ASIGNACIÓN ESCOLAR --}}
                <section class="space-y-5">
                    <div
                        class="rounded-[26px] border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900/40 dark:bg-violet-950/20">

                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 shadow-sm dark:bg-violet-950/40 dark:text-violet-300">
                                <flux:icon.academic-cap class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Asignación escolar
                                </h2>

                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    @if ($esBachillerato)
                                        En bachillerato el grupo depende de la generación y el semestre.
                                    @else
                                        Selecciona el nivel, grado, generación y grupo del alumno.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $esBachillerato ? 'xl:grid-cols-4' : 'xl:grid-cols-5' }}">

                        {{-- NIVEL --}}
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Nivel</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:select wire:model.live="nivel_id"
                                wire:key="nivel-select-{{ $nivel_id ?? 'sin-nivel' }}">
                                <flux:select.option value="">Selecciona un nivel</flux:select.option>

                                @foreach ($niveles as $nivel)
                                    <flux:select.option value="{{ $nivel->id }}">
                                        {{ $nivel->nombre }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('nivel_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- GRADO SOLO PARA PREESCOLAR, PRIMARIA Y SECUNDARIA --}}
                        @if (!$esBachillerato)
                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Grado</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>

                                <flux:select wire:model.live="grado_id"
                                    wire:key="grado-select-{{ $nivel_id ?? 'sin-nivel' }}"
                                    :disabled="!$nivel_id || $grados->isEmpty()">

                                    <flux:select.option value="">Selecciona un grado</flux:select.option>

                                    @foreach ($grados as $grado)
                                        <flux:select.option value="{{ $grado->id }}">
                                            {{ $grado->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                @error('grado_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- GENERACIÓN --}}
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Generación</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:select wire:model.live="generacion_id"
                                wire:key="generacion-select-{{ $nivel_id ?? 'sin-nivel' }}-{{ $grado_id ?? 'sin-grado' }}-{{ $esBachillerato ? 'bachillerato' : 'basica' }}"
                                :disabled="!$nivel_id || (!$esBachillerato && !$grado_id) || $generaciones->isEmpty()">

                                <flux:select.option value="">Selecciona una generación</flux:select.option>

                                @foreach ($generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->label ?? $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('generacion_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- SEMESTRE SOLO PARA BACHILLERATO --}}
                        @if ($esBachillerato)
                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Semestre</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>

                                <flux:select wire:model.live="semestre_id"
                                    wire:key="semestre-select-{{ $nivel_id ?? 'sin-nivel' }}-{{ $generacion_id ?? 'sin-generacion' }}"
                                    :disabled="!$generacion_id || $semestres->isEmpty()">

                                    <flux:select.option value="">Selecciona un semestre</flux:select.option>

                                    @foreach ($semestres as $semestre)
                                        <flux:select.option value="{{ $semestre->id }}">
                                            Semestre {{ $semestre->numero }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                @error('semestre_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- GRUPO --}}
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Grupo</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>

                            <flux:select wire:model.live="grupo_id"
                                wire:key="grupo-select-{{ $nivel_id ?? 'sin-nivel' }}-{{ $grado_id ?? 'sin-grado' }}-{{ $generacion_id ?? 'sin-generacion' }}-{{ $semestre_id ?? 'sin-semestre' }}"
                                :disabled="!$generacion_id || (!$esBachillerato && !$grado_id) || ($esBachillerato && !
                                    $semestre_id) || empty($grupos)">

                                <flux:select.option value="">Selecciona un grupo</flux:select.option>

                                @foreach ($grupos as $grupo)
                                    <flux:select.option value="{{ $grupo['id'] }}">
                                        {{ $grupo['label'] }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('grupo_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($esBachillerato)
                        <div
                            class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                            En bachillerato primero se selecciona la <b>generación</b>, después el <b>semestre</b> y al
                            final el <b>grupo</b>.
                            El grado se toma automáticamente del grupo seleccionado.
                        </div>
                    @endif
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- NACIMIENTO --}}
                <section class="space-y-5">
                    <div
                        class="rounded-[26px] border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-sm dark:bg-emerald-950/40 dark:text-emerald-300">
                                <flux:icon.map-pin class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Datos de nacimiento
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Lugar de nacimiento del alumno.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>País de nacimiento</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="pais_nacimiento" placeholder="País de nacimiento" />

                            @error('pais_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Estado de nacimiento</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="estado_nacimiento" placeholder="Estado de nacimiento" />

                            @error('estado_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Lugar de nacimiento</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="lugar_nacimiento" placeholder="Lugar de nacimiento" />

                            @error('lugar_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- TUTOR Y DOMICILIO --}}
                <section class="space-y-5">
                    <div
                        class="rounded-[26px] border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-sm dark:bg-amber-950/40 dark:text-amber-300">
                                <flux:icon.home class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Tutor y domicilio
                                </h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona el tutor y captura la dirección.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Tutor</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:select wire:model="tutor_id">
                                <flux:select.option value="">Selecciona un tutor</flux:select.option>

                                @foreach ($tutores as $tutor)
                                    <flux:select.option value="{{ $tutor->id }}">
                                        {{ trim(($tutor->nombre ?? '') . ' ' . ($tutor->apellido_paterno ?? '') . ' ' . ($tutor->apellido_materno ?? '')) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            @error('tutor_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-end md:col-span-2">
                            <label
                                class="inline-flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="copiar_direccion_tutor"
                                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                Copiar dirección del tutor
                            </label>
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Calle</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="calle" placeholder="Calle" />

                            @error('calle')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Número exterior</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="numero_exterior" placeholder="Número exterior" />

                            @error('numero_exterior')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Número interior</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="numero_interior" placeholder="Número interior" />

                            @error('numero_interior')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Colonia</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="colonia" placeholder="Colonia" />

                            @error('colonia')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Código postal</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="codigo_postal" placeholder="Código postal" />

                            @error('codigo_postal')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Municipio</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="municipio" placeholder="Municipio" />

                            @error('municipio')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Estado de residencia</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="estado_residencia" placeholder="Estado de residencia" />

                            @error('estado_residencia')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Ciudad de residencia</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <flux:input wire:model="ciudad_residencia" placeholder="Ciudad de residencia" />

                            @error('ciudad_residencia')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- FOTO --}}
                {{-- FOTO --}}
                <section class="space-y-5">
                    <div
                        class="rounded-[26px] border border-pink-200 bg-pink-50/70 p-4 dark:border-pink-900/40 dark:bg-pink-950/20">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-pink-100 text-pink-700 shadow-sm dark:bg-pink-950/40 dark:text-pink-300">
                                <flux:icon.camera class="h-5 w-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                    Fotografía
                                </h2>

                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Sube una foto del alumno si la tienes disponible.
                                </p>
                            </div>
                        </div>
                    </div>

                    @php
                        /*
            Se toma la foto guardada en la columna foto_path.
            Ejemplo: inscripciones/fotos/archivo.jpg
        */
                        $fotoActualUrl = null;

                        if (!empty($foto_actual)) {
                            $fotoActualUrl = Storage::disk('public')->exists($foto_actual)
                                ? Storage::url($foto_actual)
                                : asset('storage/' . ltrim($foto_actual, '/'));
                        }
                    @endphp

                    <div x-data="{
                        preview: null,
                        nombreArchivo: '',
                    
                        usarTemporal(event) {
                            const file = event.target.files[0];
                    
                            if (!file) {
                                return;
                            }
                    
                            this.nombreArchivo = file.name;
                    
                            const reader = new FileReader();
                    
                            reader.onload = (e) => {
                                this.preview = e.target.result;
                            };
                    
                            reader.readAsDataURL(file);
                        },
                    
                        limpiar() {
                            this.preview = null;
                            this.nombreArchivo = '';
                    
                            const input = document.getElementById('foto');
                    
                            if (input) {
                                input.value = '';
                            }
                        }
                    }" x-on:foto-limpiada.window="limpiar()"
                        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">

                        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

                        <div class="p-5 sm:p-6">
                            <div class="mb-4 flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                                    Fotografía del alumno
                                </h3>

                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Opcional
                                </span>
                            </div>

                            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                                Sube una imagen clara y reciente en formato JPG, JPEG o PNG.
                            </p>

                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                                <div class="flex items-center justify-center">
                                    <div class="relative">

                                        {{-- Loader al subir foto --}}
                                        <div wire:loading.flex wire:target="foto"
                                            class="absolute inset-0 z-20 hidden items-center justify-center rounded-[26px] bg-white/70 backdrop-blur-sm dark:bg-neutral-950/70">

                                            <div class="flex flex-col items-center gap-3">
                                                <div class="relative h-12 w-12">
                                                    <div
                                                        class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40">
                                                    </div>

                                                    <div
                                                        class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                                                    </div>
                                                </div>

                                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                    Subiendo foto...
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Preview / foto guardada --}}
                                        <div
                                            class="group relative h-52 w-52 overflow-hidden rounded-[26px] border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 shadow-lg dark:border-neutral-700 dark:from-neutral-800 dark:to-neutral-900">

                                            {{-- Vista previa inmediata con Alpine --}}
                                            <template x-if="preview">
                                                <img :src="preview" alt="Vista previa de la fotografía"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                            </template>

                                            {{-- Foto temporal de Livewire --}}
                                            @if (!empty($foto) && is_object($foto))
                                                <img src="{{ $foto->temporaryUrl() }}"
                                                    alt="Fotografía temporal del alumno"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                    x-show="!preview">

                                                {{-- Foto guardada en BD --}}
                                            @elseif (!empty($fotoActualUrl))
                                                <img src="{{ $fotoActualUrl }}" alt="Fotografía actual del alumno"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                    x-show="!preview">

                                                {{-- Estado vacío --}}
                                            @else
                                                <div x-show="!preview"
                                                    class="flex h-full w-full flex-col items-center justify-center px-4 text-center">

                                                    <div
                                                        class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md dark:bg-neutral-800">
                                                        <flux:icon.camera
                                                            class="h-8 w-8 text-slate-400 dark:text-slate-500" />
                                                    </div>

                                                    <p
                                                        class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                                                        Sin fotografía
                                                    </p>

                                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                                        Aquí se mostrará la imagen seleccionada
                                                    </p>
                                                </div>
                                            @endif

                                            <div
                                                class="absolute left-3 top-3 rounded-full bg-black/55 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                                                Foto
                                            </div>

                                            @if (!empty($fotoActualUrl))
                                                <div class="absolute bottom-3 left-3 right-3 rounded-2xl bg-emerald-500/90 px-3 py-2 text-center text-[11px] font-bold text-white shadow-lg backdrop-blur-sm"
                                                    x-show="!preview">
                                                    Foto guardada
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col justify-center">
                                    <label for="foto"
                                        class="group relative flex cursor-pointer flex-col items-center justify-center rounded-[26px] border-2 border-dashed border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 px-6 py-8 text-center transition duration-300 hover:border-sky-400 hover:shadow-lg hover:shadow-sky-500/10 dark:border-sky-900/40 dark:from-sky-950/20 dark:via-neutral-900 dark:to-indigo-950/20">

                                        <input id="foto" type="file" wire:model="foto"
                                            accept="image/png,image/jpeg,image/jpg" class="hidden"
                                            @change="usarTemporal($event)">

                                        <div
                                            class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                                            <flux:icon.cloud-arrow-up class="h-8 w-8" />
                                        </div>

                                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">
                                            Haz clic para subir tu fotografía
                                        </h4>

                                        <p class="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                                            También puedes reemplazar la imagen actual por una nueva más adelante.
                                        </p>

                                        <div
                                            class="mt-4 inline-flex items-center rounded-full bg-white px-4 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100 dark:bg-neutral-800 dark:text-sky-300 dark:ring-sky-900/30">
                                            JPG, JPEG o PNG
                                        </div>
                                    </label>

                                    <div x-show="nombreArchivo" x-transition class="mt-4">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            <flux:icon.check-circle class="h-4 w-4" />
                                            <span class="truncate" x-text="nombreArchivo"></span>
                                        </div>
                                    </div>

                                    @if (!empty($foto_actual))
                                        <div
                                            class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-neutral-700 dark:bg-neutral-800/70 dark:text-slate-300">
                                            <span class="font-bold">Ruta actual:</span>
                                            <span class="break-all">{{ $foto_actual }}</span>
                                        </div>
                                    @endif

                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <label for="foto"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01]">
                                            <flux:icon.image-plus class="mr-2 h-4 w-4" />
                                            Seleccionar foto
                                        </label>

                                        <button type="button" @click="limpiar()" wire:click="quitarFotoTemporal"
                                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                                            <flux:icon.trash-2 class="mr-2 h-4 w-4" />
                                            Quitar selección
                                        </button>
                                    </div>

                                    <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                                        Recomendación: usa una imagen vertical, con buena iluminación y fondo limpio.
                                    </p>

                                    @error('foto')
                                        <p class="mt-3 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- BOTONES --}}
                <div
                    class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-end">

                    <flux:button type="button" variant="ghost" x-data
                        x-on:click="
                            const url = localStorage.getItem('matricula_return_url');

                            if (url) {
                                window.location.href = url;
                            } else {
                                window.history.back();
                            }
                        "
                        class="cursor-pointer rounded-2xl">
                        Cancelar
                    </flux:button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="actualizarInscripcion,foto,curp" class="cursor-pointer rounded-2xl">

                        <span wire:loading.remove wire:target="actualizarInscripcion">
                            Actualizar inscripción
                        </span>

                        <span wire:loading wire:target="actualizarInscripcion">
                            Actualizando...
                        </span>
                    </flux:button>
                </div>
            </div>

            {{-- LOADER AL ACTUALIZAR --}}
            <div wire:loading.flex wire:target="actualizarInscripcion"
                class="absolute inset-0 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">

                <div
                    class="rounded-3xl border border-slate-200 bg-white px-6 py-5 text-center shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                    <div
                        class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-sky-200 border-t-sky-600">
                    </div>

                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Actualizando inscripción...
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
