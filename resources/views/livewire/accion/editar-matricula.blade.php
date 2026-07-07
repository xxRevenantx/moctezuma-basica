<div x-data="{
    cargandoPagina: true,
    regresandoMatricula: false,

    llaveRetorno() {
        return 'matricula_return_context_' + @js($slug_nivel);
    },

    regresarMatricula() {
        if (this.regresandoMatricula) return;

        let url = @js(route('submodulos.accion', [
            'slug_nivel' => $slug_nivel,
            'accion' => 'matricula',
        ]));

        const raw = localStorage.getItem(this.llaveRetorno());

        if (raw) {
            try {
                const contexto = JSON.parse(raw);

                if (contexto.url && contexto.expires_at && Date.now() <= Number(contexto.expires_at)) {
                    url = contexto.url;
                    localStorage.setItem('matricula_return_pending', '1');
                } else {
                    localStorage.removeItem(this.llaveRetorno());
                }
            } catch (error) {
                localStorage.removeItem(this.llaveRetorno());
            }
        }

        this.regresandoMatricula = true;
        setTimeout(() => window.location.href = url, 300);
    }
}" x-init="setTimeout(() => cargandoPagina = false, 600)" class="space-y-6">

    {{-- Loader inicial --}}
    <div x-cloak x-show="cargandoPagina" x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/80 backdrop-blur-md dark:bg-neutral-950/80">
        <div
            class="mx-4 w-full max-w-sm rounded-[28px] border border-indigo-100 bg-white/95 p-7 text-center shadow-2xl shadow-indigo-500/20 dark:border-indigo-900/40 dark:bg-neutral-900/95">
            <div class="relative mx-auto mb-5 flex h-20 w-20 items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-indigo-100 dark:border-indigo-900/40"></div>
                <div
                    class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-r-sky-500 border-t-indigo-500">
                </div>
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-600 to-sky-500 text-white shadow-lg shadow-indigo-500/30">
                    <flux:icon.user class="h-5 w-5" />
                </div>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Cargando matrícula</h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Consultando la información del alumno...</p>
            <div class="mt-5 flex items-center justify-center gap-1.5">
                <span
                    class="h-2.5 w-2.5 animate-bounce rounded-full bg-indigo-500 [animation-delay:-0.3s]"></span>
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-blue-500 [animation-delay:-0.15s]"></span>
                <span class="h-2.5 w-2.5 animate-bounce rounded-full bg-sky-500"></span>
            </div>
        </div>
    </div>

    {{-- Loader de regreso --}}
    <div x-cloak x-show="regresandoMatricula" x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-md">
        <div
            class="mx-4 w-full max-w-sm rounded-[28px] border border-emerald-100 bg-white/95 p-7 text-center shadow-2xl shadow-emerald-500/20 dark:border-emerald-900/40 dark:bg-neutral-900/95">
            <div
                class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-emerald-100 border-t-emerald-600">
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Regresando a Matrícula</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Restaurando filtros y posición...</p>
        </div>
    </div>

    {{-- Loader al guardar --}}
    <div wire:loading.flex wire:target="actualizarInscripcion"
        class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-950/65 p-4 backdrop-blur-md">
        <div
            class="mx-4 w-full max-w-sm rounded-[28px] border border-sky-100 bg-white/95 p-7 text-center shadow-2xl shadow-sky-500/20 dark:border-sky-900/40 dark:bg-neutral-900/95">
            <div class="relative mx-auto mb-5 flex h-16 w-16 items-center justify-center">
                <div
                    class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-r-indigo-500 border-t-sky-500">
                </div>
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white">
                    <flux:icon.check class="h-5 w-5" />
                </div>
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Guardando cambios</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Actualizando la matrícula del alumno...</p>
        </div>
    </div>

    <form wire:submit.prevent="actualizarInscripcion" class="space-y-6">
        <div
            class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

            <div class="p-5 sm:p-6 lg:p-8">
                {{-- Encabezado igual a Nueva inscripción --}}
                <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                                Editar matrícula
                            </h1>

                            <span
                                class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                {{ str($estatus)->replace('_', ' ')->title() }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Actualiza los datos del alumno y su asignación escolar.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($esBachillerato)
                            <span
                                class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                Modo bachillerato activo
                            </span>
                        @endif

                        @if (auth()->user()?->is_admin && $InscripcionId)
                            <a href="{{ route('misrutas.expedientes.show', $InscripcionId) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                wire:navigate>
                                <flux:icon name="folder-lock" class="size-4" />
                                Expediente digital
                            </a>
                        @endif

                        <flux:button type="button" variant="ghost" x-on:click="regresarMatricula()"
                            class="cursor-pointer rounded-2xl border border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                            <span class="inline-flex items-center gap-2">
                                <flux:icon.arrow-left class="h-4 w-4" />
                                Regresar
                            </span>
                        </flux:button>
                    </div>
                </div>

                {{-- Expediente digital --}}
                @if (auth()->user()?->is_admin && !empty($resumenDocumental))
                    <section
                        class="mb-6 rounded-[26px] border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-sky-50 p-5 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/10 dark:via-neutral-900 dark:to-sky-950/10">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-sm">
                                        <flux:icon name="folder-lock" class="size-5" />
                                    </div>
                                    <div>
                                        <h3 class="font-black text-slate-900 dark:text-white">
                                            Expediente digital del alumno
                                        </h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ $resumenDocumental['completados'] }}/{{ $resumenDocumental['total'] }}
                                            documentos recibidos · {{ $resumenDocumental['pendientes'] }} pendientes
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-sky-500"
                                        style="width: {{ $resumenDocumental['porcentaje'] }}%"></div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($resumenDocumental['items'] as $itemDocumento)
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black {{ $itemDocumento['presente'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                            <flux:icon :name="$itemDocumento['presente'] ? 'check' : 'clock-3'"
                                                class="size-3.5" />
                                            {{ $itemDocumento['etiqueta'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ route('misrutas.expedientes.show', $InscripcionId) }}"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg"
                                wire:navigate>
                                Administrar documentos
                                <flux:icon name="arrow-right" class="size-4" />
                            </a>
                        </div>
                    </section>
                @endif

                @if ($curpSuccess)
                    <div
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <p class="font-semibold">CURP encontrada</p>
                        <p class="mt-1">{{ $curpSuccess }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                        <p class="font-black">Revisa la información marcada antes de guardar.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- DATOS PERSONALES --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                            <flux:icon.user class="h-5 w-5" />
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Datos personales</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Información básica del alumno.</p>
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

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <div class="flex-1">
                                    <flux:input wire:model="curp" maxlength="18" placeholder="Ingresa la CURP" />
                                </div>

                                <flux:button type="button" variant="ghost" wire:click="consultarCurp"
                                    wire:loading.attr="disabled" wire:target="consultarCurp"
                                    class="cursor-pointer rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                    <span wire:loading.remove wire:target="consultarCurp">Consultar CURP</span>
                                    <span wire:loading wire:target="consultarCurp">Consultando...</span>
                                </flux:button>
                            </div>

                            @if ($curpError)
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $curpError }}</p>
                            @endif
                            @error('curp')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Matrícula</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    Editable
                                </span>
                            </div>
                            <flux:input wire:model="matricula" placeholder="Ingresa o edita la matrícula" />
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
                                <flux:label>Fecha de ingreso al plantel</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>
                            <flux:input type="date" wire:model="fecha_ingreso_plantel" />
                            @error('fecha_ingreso_plantel')
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
                                <flux:select.option value="">Selecciona un periodo</flux:select.option>
                                @foreach ($ciclos as $ciclo)
                                    <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->ciclo }}</flux:select.option>
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
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                            <flux:icon.academic-cap class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Asignación escolar</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $esBachillerato ? 'En bachillerato el grado se obtiene automáticamente desde el semestre y grupo.' : 'Selecciona nivel, grado, generación y grupo.' }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $esBachillerato ? 'xl:grid-cols-4' : 'xl:grid-cols-4' }}">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Nivel</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>
                            <flux:select wire:model.live="nivel_id">
                                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                @foreach ($niveles as $nivel)
                                    <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('nivel_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (!$esBachillerato)
                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>Grado</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                        Obligatorio
                                    </span>
                                </div>
                                <flux:select wire:model.live="grado_id" :disabled="!$nivel_id || $grados->isEmpty()">
                                    <flux:select.option value="">Selecciona un grado</flux:select.option>
                                    @foreach ($grados as $grado)
                                        <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('grado_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Generación</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>
                            <flux:select wire:model.live="generacion_id"
                                :disabled="!$nivel_id || (!$esBachillerato && !$grado_id) || $generaciones->isEmpty()">
                                <flux:select.option value="">Selecciona una generación</flux:select.option>
                                @foreach ($generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->etiqueta }}{{ $generacion->status ? '' : ' · Inactiva' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('generacion_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

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

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Grupo</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
                                    Obligatorio
                                </span>
                            </div>
                            <flux:select wire:model.live="grupo_id"
                                :disabled="!$generacion_id || ($esBachillerato && !$semestre_id) || (!$esBachillerato && !$grado_id) || empty($grupos)">
                                <flux:select.option value="">Selecciona un grupo</flux:select.option>
                                @foreach ($grupos as $grupo)
                                    <flux:select.option value="{{ $grupo['id'] }}">{{ $grupo['label'] }}</flux:select.option>
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
                            En bachillerato selecciona <b>generación</b>, después <b>semestre</b> y al final
                            <b>grupo</b>. El <b>grado</b> se toma automáticamente desde el grupo.
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                            Para preescolar, primaria y secundaria la matrícula queda ligada a <b>nivel</b>,
                            <b>grado</b>, <b>generación</b> y <b>grupo</b>.
                        </div>
                    @endif
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- CONTROL ADMINISTRATIVO --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                            <flux:icon.shield-check class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Control administrativo</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Los cambios académicos o de estatus quedan registrados en la bitácora.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Estatus</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700">
                                    Obligatorio
                                </span>
                            </div>
                            <flux:select wire:model="estatus">
                                @foreach (\App\Services\GestionAcademicaService::ESTATUS as $estado)
                                    <flux:select.option value="{{ $estado }}">
                                        {{ str($estado)->replace('_', ' ')->title() }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('estatus')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Fecha del estatus</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700">
                                    Obligatorio
                                </span>
                            </div>
                            <flux:input type="date" wire:model="fecha_estatus" />
                            @error('fecha_estatus')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 xl:col-span-2">
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Motivo del cambio académico o de estatus</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                    Según cambio
                                </span>
                            </div>
                            <flux:textarea wire:model="motivo_cambio" rows="3"
                                placeholder="Describe la razón de la modificación" />
                            <p class="mt-1 text-xs text-slate-500">
                                Es obligatorio al cambiar generación, grado, semestre, grupo o estatus.
                            </p>
                            @error('motivo_cambio')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div
                            class="md:col-span-2 xl:col-span-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                            <flux:checkbox wire:model="confirmar_cambio_academico"
                                label="Confirmo que, si cambio generación, grado, semestre o grupo, se reemplazará la asignación académica actual del alumno." />
                            @error('confirmar_cambio_academico')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- NACIMIENTO --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <flux:icon.map-pin class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Datos de nacimiento</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Lugar de nacimiento del alumno.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>País de nacimiento</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
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
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
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
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
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
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                            <flux:icon.home class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Tutor y domicilio</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Selecciona el tutor y actualiza la dirección.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <div class="mb-1 flex items-center gap-2">
                                <flux:label>Tutor</flux:label>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                    Opcional
                                </span>
                            </div>
                            <flux:select wire:model.live="tutor_id">
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

                        @foreach ([
                            'calle' => ['Calle', 'Calle'],
                            'numero_exterior' => ['Número exterior', 'Número exterior'],
                            'numero_interior' => ['Número interior', 'Número interior'],
                            'colonia' => ['Colonia', 'Colonia'],
                            'codigo_postal' => ['Código postal', 'Código postal'],
                            'municipio' => ['Municipio', 'Municipio'],
                            'estado_residencia' => ['Estado de residencia', 'Estado de residencia'],
                            'ciudad_residencia' => ['Ciudad de residencia', 'Ciudad de residencia'],
                        ] as $campo => [$etiqueta, $placeholder])
                            <div>
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:label>{{ $etiqueta }}</flux:label>
                                    <span
                                        class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                        Opcional
                                    </span>
                                </div>
                                <flux:input wire:model="{{ $campo }}" placeholder="{{ $placeholder }}" />
                                @error($campo)
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

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
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">Fotografía</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Conserva la fotografía actual o selecciona una nueva.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div x-data="{
                        preview: null,
                        nombreArchivo: '',

                        usarTemporal(event) {
                            const file = event.target.files[0];
                            if (!file) return;

                            this.nombreArchivo = file.name;
                            const reader = new FileReader();
                            reader.onload = (e) => this.preview = e.target.result;
                            reader.readAsDataURL(file);
                        },

                        limpiar() {
                            this.preview = null;
                            this.nombreArchivo = '';
                            const input = document.getElementById('foto-editar');
                            if (input) input.value = '';
                        }
                    }" x-on:foto-limpiada.window="limpiar()"
                        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">

                        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-indigo-500 to-fuchsia-500"></div>

                        <div class="p-5 sm:p-6">
                            <div class="mb-4 flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-800 dark:text-white">Fotografía del alumno</h3>
                                <span
                                    class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-700">
                                    Opcional
                                </span>
                            </div>

                            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                                La nueva imagen reemplazará la fotografía actual al guardar los cambios.
                            </p>

                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                                <div class="flex items-center justify-center">
                                    <div class="relative">
                                        <div wire:loading.flex wire:target="foto"
                                            class="absolute inset-0 z-20 hidden items-center justify-center rounded-[26px] bg-white/70 backdrop-blur-sm dark:bg-neutral-950/70">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="relative h-12 w-12">
                                                    <div
                                                        class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40">
                                                    </div>
                                                    <div
                                                        class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-r-indigo-500 border-t-sky-500">
                                                    </div>
                                                </div>
                                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                    Cargando foto...
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="group relative h-52 w-52 overflow-hidden rounded-[26px] border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 shadow-lg dark:border-neutral-700 dark:from-neutral-800 dark:to-neutral-900">
                                            <template x-if="preview">
                                                <img :src="preview" alt="Vista previa de la fotografía"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                            </template>

                                            @if ($foto_actual_existe && $foto_actual_url)
                                                <img src="{{ $foto_actual_url }}"
                                                    alt="Fotografía actual del alumno"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                    x-show="!preview">
                                            @else
                                                <div x-show="!preview"
                                                    class="flex h-full w-full flex-col items-center justify-center px-4 text-center">
                                                    <div
                                                        class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md dark:bg-neutral-800">
                                                        <flux:icon.camera
                                                            class="h-8 w-8 text-slate-400 dark:text-slate-500" />
                                                    </div>
                                                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                                                        {{ $foto_actual ? 'Fotografía no disponible' : 'Sin fotografía' }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                                        {{ $foto_actual ? 'Vuelve a cargarla porque el archivo físico no existe.' : 'Aquí se mostrará la imagen seleccionada' }}
                                                    </p>
                                                </div>
                                            @endif

                                            <div
                                                class="absolute left-3 top-3 rounded-full bg-black/55 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                                                {{ $foto_actual_existe ? 'Foto actual' : ($foto_actual ? 'Volver a cargar foto' : 'Foto') }}
                                            </div>

                                            <div x-show="preview" x-transition
                                                class="absolute bottom-3 left-3 right-3 rounded-2xl bg-emerald-500/90 px-3 py-2 text-center text-[11px] font-bold text-white shadow-lg backdrop-blur-sm">
                                                Nueva foto seleccionada
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col justify-center">
                                    <label for="foto-editar"
                                        class="group relative flex cursor-pointer flex-col items-center justify-center rounded-[26px] border-2 border-dashed border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 px-6 py-8 text-center transition duration-300 hover:border-sky-400 hover:shadow-lg hover:shadow-sky-500/10 dark:border-sky-900/40 dark:from-sky-950/20 dark:via-neutral-900 dark:to-indigo-950/20">
                                        <input id="foto-editar" type="file" wire:model="foto"
                                            accept="image/png,image/jpeg,image/webp,.jpg,.jpeg,.png,.webp" class="hidden"
                                            @change="usarTemporal($event)">

                                        <div
                                            class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                                            <flux:icon.cloud-arrow-up class="h-8 w-8" />
                                        </div>

                                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">
                                            Haz clic para seleccionar una nueva fotografía
                                        </h4>
                                        <p class="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                                            La fotografía actual se conserva mientras no guardes una nueva.
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

                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <label for="foto-editar"
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

                                    @error('foto')
                                        <p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div
                    class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-end">
                    <flux:button type="button" variant="ghost" x-on:click="regresarMatricula()"
                        class="cursor-pointer rounded-2xl">
                        Cancelar
                    </flux:button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="actualizarInscripcion,foto,consultarCurp" class="cursor-pointer rounded-2xl">
                        <span wire:loading.remove wire:target="actualizarInscripcion">Guardar cambios</span>
                        <span wire:loading wire:target="actualizarInscripcion">Guardando...</span>
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
