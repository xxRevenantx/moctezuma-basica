<div class="space-y-6">
    <form wire:submit.prevent="guardar" class="space-y-6">
        <div
            class="relative overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

            <div class="p-5 sm:p-6 lg:p-8">
                {{-- Encabezado --}}
                <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                            Nueva inscripción
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Captura los datos del alumno y su asignación escolar.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($esBachillerato)
                            <span
                                class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                                Modo bachillerato activo
                            </span>
                        @endif

                        <span
                            class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                            Matrícula automática según selección
                        </span>
                    </div>
                </div>

                {{-- DATOS PERSONALES --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
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
                            @error('curp')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                            @if ($curpError)
                                <p class="mt-2 text-xs font-semibold text-amber-600">{{ $curpError }}</p>
                            @endif
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
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                            <flux:icon.academic-cap class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Asignación escolar
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $esBachillerato ? 'En bachillerato el grado se obtiene automáticamente desde el semestre y grupo.' : 'Selecciona nivel, grado, generación y grupo.' }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $esBachillerato ? 'xl:grid-cols-4' : 'xl:grid-cols-5' }}">
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
                                    <flux:select.option value="{{ $nivel->id }}">
                                        {{ $nivel->nombre }}
                                    </flux:select.option>
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
                                        {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
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
                                :disabled="!$generacion_id || ($esBachillerato && !$semestre_id) || (!$esBachillerato && !
                                    $grado_id) || empty($grupos)">
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
                            En bachillerato selecciona <b>generación</b>, después <b>semestre</b> y al final
                            <b>grupo</b>.
                            El <b>grado</b> se toma automáticamente desde el grupo para respetar la estructura de la
                            base de datos.
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                            Para preescolar, primaria y secundaria la inscripción queda ligada a <b>nivel</b>,
                            <b>grado</b>,
                            <b>generación</b> y <b>grupo</b>.
                        </div>
                    @endif
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
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Datos de nacimiento
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Lugar de nacimiento del alumno.
                            </p>
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
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
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

                        <div class="md:col-span-2 flex items-end">
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
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300">
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

                    <div x-data="{
                        preview: null,
                        nombreArchivo: '',
                        cargando: false,
                        usarTemporal(event) {
                            const file = event.target.files[0];
                            if (!file) return;
                    
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
                            if (input) input.value = '';
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

                                        <div
                                            class="group relative h-52 w-52 overflow-hidden rounded-[26px] border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 shadow-lg dark:border-neutral-700 dark:from-neutral-800 dark:to-neutral-900">
                                            <template x-if="preview">
                                                <img :src="preview" alt="Vista previa"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                            </template>

                                            @if (!empty($foto) && is_object($foto))
                                                <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa temporal"
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

                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <label for="foto"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01]">
                                            <flux:icon.image-plus class="mr-2 h-4 w-4" />
                                            Seleccionar foto
                                        </label>

                                        <button type="button" @click="limpiar()" wire:click="quitarFotoTemporal"
                                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                                            <flux:icon.trash-2 class="mr-2 h-4 w-4" />
                                            Quitar
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

                <div
                    class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-end">
                    <flux:button type="button" variant="ghost" wire:click="cancelar"
                        class="cursor-pointer rounded-2xl">
                        Cancelar
                    </flux:button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="guardar,foto,curp" class="cursor-pointer rounded-2xl">
                        <span wire:loading.remove wire:target="guardar">Guardar inscripción</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </flux:button>
                </div>
            </div>

            <div wire:loading.flex wire:target="guardar"
                class="absolute inset-0 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
                <div
                    class="rounded-3xl border border-slate-200 bg-white px-6 py-5 text-center shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                    <div
                        class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-sky-200 border-t-sky-600">
                    </div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Guardando inscripción...
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
