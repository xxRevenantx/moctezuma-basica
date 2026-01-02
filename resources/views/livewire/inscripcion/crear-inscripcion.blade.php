<div class="space-y-6">
    <!-- HERO HEADER -->
    <div
        class="relative overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="absolute inset-0">
            <div
                class="absolute -top-32 -right-32 h-72 w-72 rounded-full bg-gradient-to-br from-sky-500/25 via-blue-600/20 to-indigo-600/25 blur-3xl">
            </div>
            <div
                class="absolute -bottom-32 -left-32 h-72 w-72 rounded-full bg-gradient-to-tr from-violet-500/20 via-fuchsia-500/15 to-rose-500/20 blur-3xl">
            </div>
        </div>

        <div class="relative p-6 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-neutral-900 px-3 py-1 text-xs font-semibold text-white
                                dark:bg-white dark:text-neutral-900">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span>
                        Alta / Edición de alumno
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                        Registro del alumno
                    </h1>
                    <p class="max-w-2xl text-sm sm:text-base text-neutral-600 dark:text-neutral-300">
                        Completa la información. Los campos obligatorios están marcados con <span
                            class="font-semibold text-red-600">*</span>.
                        Los opcionales llevan el badge <span
                            class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold text-neutral-700 ring-1 ring-inset ring-neutral-200 dark:bg-neutral-800/70 dark:text-neutral-200 dark:ring-neutral-700">Opcional</span>.
                    </p>
                </div>

                <!-- Quick status -->
                <div class="flex flex-col sm:items-end gap-2">
                    <div
                        class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white/70 px-4 py-2 text-xs text-neutral-700 shadow-sm backdrop-blur
                               dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-200">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Listo para guardar
                    </div>

                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                        Sugerencia: usa “Guardar” al final.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $BadgeOpcional =
            '<span class="ml-2 inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-semibold text-neutral-700 ring-1 ring-inset ring-neutral-200 dark:bg-neutral-800/70 dark:text-neutral-200 dark:ring-neutral-700">Opcional</span>';
        $SectionIcon = function ($svg) {
            return '<span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow ring-1 ring-white/15">' .
                $svg .
                '</span>';
        };
        $Divider = '<div class="h-px w-full bg-neutral-200/70 dark:bg-neutral-800/70"></div>';
    @endphp

    <!-- MAIN CARD -->
    <div class="rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <!-- fancy top accent -->
        <div class="h-1.5 w-full rounded-t-3xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        <div class="p-5 sm:p-7 space-y-8">
            <!-- Loading overlay (para catálogos/guardar) -->
            <div class="pointer-events-none relative" aria-live="polite">
                <div wire:loading
                    class="pointer-events-auto absolute inset-0 z-30 rounded-3xl bg-white/60 backdrop-blur-sm
                           dark:bg-neutral-950/50">
                    <div class="grid h-full place-items-center p-6">
                        <div
                            class="w-full max-w-sm rounded-3xl border border-neutral-200 bg-white p-5 shadow-xl
                                    dark:border-neutral-800 dark:bg-neutral-900">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 animate-spin rounded-full border-4 border-neutral-200 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-neutral-900 dark:text-white">Cargando…</div>
                                    <div class="text-xs text-neutral-600 dark:text-neutral-400">Procesando información.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: ACADÉMICO -->
            <div
                class="rounded-3xl border border-neutral-200 bg-neutral-50/50 p-5 sm:p-6 dark:border-neutral-800 dark:bg-neutral-950/20">
                <div class="flex items-start gap-4">
                    {!! $SectionIcon(
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 2.625A1 1 0 002 5.64v4.72a1 1 0 00.606.92l7 3.111a1 1 0 00.788 0l7-3.11A1 1 0 0018 10.36V5.64a1 1 0 00-.606-.919l-7-2.64z"/><path d="M4 10.894V14a2 2 0 002 2h8a2 2 0 002-2v-3.106l-6.606 2.936a2 2 0 01-1.788 0L4 10.894z"/></svg>',
                    ) !!}
                    <div class="flex-1">
                        <h2 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-white">Datos académicos
                        </h2>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">Asignación escolar.</p>

                        <div class="mt-5 space-y-5">
                            {!! $Divider !!}

                            {{-- si es bachillerato mostramos 5 columnas, si no 4 --}}
                            <div
                                class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $esBachillerato ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }}">

                                {{-- NIVEL --}}
                                <flux:select label="Selecciona un nivel" wire:model.live="nivel_id" wire:key="nivel"
                                    placeholder="Selecciona nivel...">
                                    <flux:select.option value="">---Selecciona nivel...</flux:select.option>
                                    @forelse ($niveles as $n)
                                        <flux:select.option value="{{ $n->id }}">{{ $n->nombre }}
                                        </flux:select.option>
                                    @empty
                                        <flux:select.option value="" disabled>No hay niveles disponibles
                                        </flux:select.option>
                                    @endforelse
                                </flux:select>
                                <flux:error name="nivel_id" />

                                {{-- GRADO --}}
                                <flux:select label="Selecciona un grado" wire:model.live="grado_id"
                                    wire:key="grado-{{ $nivel_id ?? 'x' }}"
                                    placeholder="{{ $nivel_id ? 'Selecciona grado...' : 'Primero selecciona nivel' }}"
                                    :disabled="!$nivel_id || ($grados->count() ?? 0) === 0">
                                    <flux:select.option value="">---Selecciona grado...</flux:select.option>
                                    @forelse ($grados as $g)
                                        <flux:select.option value="{{ $g->id }}">{{ $g->nombre }}°
                                        </flux:select.option>
                                    @empty
                                        <flux:select.option value="" disabled>No hay grados para este nivel
                                        </flux:select.option>
                                    @endforelse
                                </flux:select>
                                <flux:error name="grado_id" />

                                {{-- GENERACIÓN --}}
                                <flux:select label="Selecciona una generación" wire:model.live="generacion_id"
                                    wire:key="gen-{{ ($nivel_id ?? 'x') . '-' . ($grado_id ?? 'x') }}"
                                    placeholder="{{ $grado_id ? 'Selecciona generación...' : 'Primero selecciona grado' }}"
                                    :disabled="!$nivel_id || !$grado_id || ($generaciones->count() ?? 0) === 0">
                                    <flux:select.option value="">---Selecciona generación...</flux:select.option>
                                    @forelse ($generaciones as $gen)
                                        <flux:select.option value="{{ $gen->id }}">
                                            {{ $gen->anio_ingreso }}–{{ $gen->anio_egreso }}</flux:select.option>
                                    @empty
                                        <flux:select.option value="" disabled>No hay generaciones para este grado
                                        </flux:select.option>
                                    @endforelse
                                </flux:select>
                                <flux:error name="generacion_id" />

                                {{-- SEMESTRE (solo bachillerato) --}}
                                @if ($esBachillerato)
                                    <flux:select label="Selecciona un semestre" wire:model.live="semestre_id"
                                        wire:key="semestre-{{ ($nivel_id ?? 'x') . '-' . ($grado_id ?? 'x') . '-' . ($generacion_id ?? 'x') }}"
                                        placeholder="{{ $generacion_id ? 'Selecciona semestre...' : 'Primero selecciona generación' }}"
                                        :disabled="!$nivel_id || !$grado_id || !$generacion_id || ($semestres->count() ?? 0) === 0">
                                        <flux:select.option value="">---Selecciona semestre...
                                        </flux:select.option>

                                        @forelse ($semestres as $s)
                                            <flux:select.option value="{{ $s->id }}">
                                                {{ $s->numero }}°
                                            </flux:select.option>
                                        @empty
                                            <flux:select.option value="" disabled>
                                                No hay semestres para esta selección
                                            </flux:select.option>
                                        @endforelse
                                    </flux:select>
                                    <flux:error name="semestre_id" />
                                @endif


                                {{-- GRUPO --}}
                                <flux:select label="Selecciona el grupo" wire:model.live="grupo_id"
                                    wire:key="grupo-{{ ($nivel_id ?? 'x') . '-' . ($grado_id ?? 'x') . '-' . ($generacion_id ?? 'x') . '-' . ($semestre_id ?? 'x') }}"
                                    placeholder="{{ $generacion_id ? 'Selecciona grupo...' : 'Primero selecciona generación' }}"
                                    :disabled="!$nivel_id || !$grado_id || !$generacion_id || (count($grupos) === 0) || (
                                        $esBachillerato && !$semestre_id)">
                                    <flux:select.option value="">---Selecciona grupo...</flux:select.option>
                                    @forelse ($grupos as $gr)
                                        <flux:select.option value="{{ $gr['id'] }}">{{ $gr['label'] }}
                                        </flux:select.option>
                                    @empty
                                        <flux:select.option value="" disabled>No hay grupos para esta selección
                                        </flux:select.option>
                                    @endforelse
                                </flux:select>
                                <flux:error name="grupo_id" />
                            </div>


                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: IDENTIDAD -->
            <div
                class="rounded-3xl border border-neutral-200 bg-neutral-50/50 p-5 sm:p-6 dark:border-neutral-800 dark:bg-neutral-950/20">
                <div class="flex items-start gap-4">
                    {!! $SectionIcon(
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a5 5 0 100 10 5 5 0 000-10zM4 14a6 6 0 1112 0v1a2 2 0 01-2 2H6a2 2 0 01-2-2v-1z" clip-rule="evenodd"/></svg>',
                    ) !!}
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-white">Identidad
                                </h2>
                                <p class="text-sm text-neutral-600 dark:text-neutral-400">Datos personales y de
                                    registro.</p>
                            </div>
                            <span
                                class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs text-neutral-700 shadow-sm
                                         dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-200">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Validación en formulario
                            </span>
                        </div>

                        {{-- ERRORES DE VALIDACIÓN --}}
                        {{-- ERRORES DE VALIDACIÓN ATRACTIVOS --}}
                        @if ($errors->any())
                            <div
                                class="mt-4 rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-rose-50 p-4 shadow-sm
               dark:border-rose-900/60 dark:from-rose-950/30 dark:via-neutral-950/10 dark:to-rose-950/20">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow ring-1 ring-white/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10A8 8 0 11 2 10a8 8 0 0116 0Zm-8-4a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1Zm0 8a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 14Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <div>
                                                <div class="text-sm font-extrabold text-rose-700 dark:text-rose-200">
                                                    Hay {{ $errors->count() }} detalle(s) por corregir
                                                </div>
                                                <div class="text-xs text-rose-700/80 dark:text-rose-200/80">
                                                    Revisa los campos marcados en rojo.
                                                </div>
                                            </div>

                                            <span
                                                class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-semibold text-rose-700 shadow-sm
                               dark:border-rose-900/60 dark:bg-neutral-900 dark:text-rose-200">
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                Validación
                                            </span>
                                        </div>

                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                            @foreach ($errors->all() as $err)
                                                <div
                                                    class="flex items-start gap-2 rounded-2xl border border-rose-100 bg-white px-3 py-2 text-sm text-rose-700 shadow-sm
                                   dark:border-rose-900/40 dark:bg-neutral-900/50 dark:text-rose-200">
                                                    <span
                                                        class="mt-0.5 inline-block h-2 w-2 rounded-full bg-rose-500"></span>
                                                    <span class="leading-snug">{{ $err }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif





                        <div class="mt-5 space-y-5">
                            {!! $Divider !!}

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <flux:field>
                                    <flux:label>CURP <span class="text-red-600">*</span></flux:label>
                                    <flux:input wire:model.live.debounce.600ms="curp"
                                        placeholder="Ej. NUPC950101HGRXXX09" />
                                    <flux:error name="curp" />
                                    @if ($consultandoCurp)
                                        <p class="text-sm text-sky-600">Consultando CURP…</p>
                                    @endif

                                    @if ($curpError)
                                        <p class="text-sm text-rose-600">{{ $curpError }}</p>
                                    @endif
                                </flux:field>

                                <flux:field>
                                    <flux:label>Matrícula <span class="text-red-600">*</span></flux:label>
                                    <flux:input wire:model.defer="matricula" :disabled="true" readonly
                                        class="cursor-not-allowed opacity-80" />
                                    <flux:error name="matricula" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Folio {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="folio" placeholder="Ej. FOL-00001" />
                                    <flux:error name="folio" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Nombre <span class="text-red-600">*</span></flux:label>
                                    <flux:input wire:model.defer="nombre" placeholder="Ej. José Luis" />
                                    <flux:error name="nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Apellido paterno <span class="text-red-600">*</span></flux:label>
                                    <flux:input wire:model.defer="apellido_paterno" placeholder="Ej. Gutiérrez" />
                                    <flux:error name="apellido_paterno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Apellido materno {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="apellido_materno" placeholder="Ej. Mendoza" />
                                    <flux:error name="apellido_materno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Fecha de nacimiento <span class="text-red-600">*</span></flux:label>
                                    <flux:input type="date" wire:model.defer="fecha_nacimiento" />
                                    <flux:error name="fecha_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Género <span class="text-red-600">*</span></flux:label>
                                    <flux:select wire:model="genero" placeholder="Selecciona...">
                                        <flux:select.option value="H">Hombre</flux:select.option>
                                        <flux:select.option value="M">Mujer</flux:select.option>
                                    </flux:select>
                                    <flux:error name="genero" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>País de nacimiento {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="pais_nacimiento" placeholder="Ej. México" />
                                    <flux:error name="pais_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Estado de nacimiento {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="estado_nacimiento" placeholder="Ej. Guerrero" />
                                    <flux:error name="estado_nacimiento" />
                                </flux:field>

                                <flux:field class="sm:col-span-2 lg:col-span-2">
                                    <flux:label>Lugar de nacimiento {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="lugar_nacimiento"
                                        placeholder="Ej. Ciudad Altamirano" />
                                    <flux:error name="lugar_nacimiento" />
                                </flux:field>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: DIRECCIÓN -->
            <div
                class="rounded-3xl border border-neutral-200 bg-neutral-50/50 p-5 sm:p-6 dark:border-neutral-800 dark:bg-neutral-950/20">
                <div class="flex items-start gap-4">
                    {!! $SectionIcon(
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 1.293a1 1 0 00-1.414 0l-7 7A1 1 0 003 9h1v8a2 2 0 002 2h2a1 1 0 001-1v-4h2v4a1 1 0 001 1h2a2 2 0 002-2V9h1a1 1 0 00.707-1.707l-7-7z"/></svg>',
                    ) !!}
                    <div class="flex-1">
                        <h2 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-white">Dirección</h2>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">Domicilio y residencia.</p>

                        <div class="mt-5 space-y-5">
                            {!! $Divider !!}

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <flux:field class="lg:col-span-2">
                                    <flux:label>Calle {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="calle" placeholder="Ej. Francisco I. Madero" />
                                    <flux:error name="calle" />
                                </flux:field>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 lg:col-span-1">
                                    <flux:field>
                                        <flux:label>Núm. exterior {!! $BadgeOpcional !!}</flux:label>
                                        <flux:input wire:model.defer="numero_exterior" placeholder="Ej. 800" />
                                        <flux:error name="numero_exterior" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Núm. interior {!! $BadgeOpcional !!}</flux:label>
                                        <flux:input wire:model.defer="numero_interior" placeholder="Ej. 2B" />
                                        <flux:error name="numero_interior" />
                                    </flux:field>
                                </div>

                                <flux:field class="sm:col-span-2 lg:col-span-2">
                                    <flux:label>Colonia {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="colonia" placeholder="Ej. Esquipula" />
                                    <flux:error name="colonia" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Código postal {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="codigo_postal" placeholder="Ej. 40662" />
                                    <flux:error name="codigo_postal" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Municipio {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="municipio" placeholder="Ej. Pungarabato" />
                                    <flux:error name="municipio" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Estado de residencia {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="estado_residencia" placeholder="Ej. Guerrero" />
                                    <flux:error name="estado_residencia" />
                                </flux:field>

                                <flux:field class="sm:col-span-2 lg:col-span-2">
                                    <flux:label>Ciudad de residencia {!! $BadgeOpcional !!}</flux:label>
                                    <flux:input wire:model.defer="ciudad_residencia"
                                        placeholder="Ej. Cd. Altamirano" />
                                    <flux:error name="ciudad_residencia" />
                                </flux:field>
                            </div>
                        </div>
                    </div>
                </div>
            </div>




            <!-- SECTION: FOTO -->
            <div
                class="rounded-3xl border border-neutral-200 bg-neutral-50/50 p-5 sm:p-6 dark:border-neutral-800 dark:bg-neutral-950/20">
                <div class="flex items-start gap-4">
                    {!! $SectionIcon(
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0015.586 3H4zm6 5a3 3 0 100 6 3 3 0 000-6z" clip-rule="evenodd"/></svg>',
                    ) !!}
                    <div class="flex-1">
                        <h2 class="text-base sm:text-lg font-bold text-neutral-900 dark:text-white">Fotografía</h2>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">Sube una foto del alumno (JPG/PNG).
                        </p>

                        <div class="mt-5 space-y-5">
                            {!! $Divider !!}

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <!-- Dropzone -->
                                <div class="lg:col-span-2">
                                    <div
                                        class="relative overflow-hidden rounded-3xl border border-dashed border-neutral-300 bg-white p-6 shadow-sm
                                               dark:border-neutral-700 dark:bg-neutral-900">
                                        <div class="absolute inset-0">
                                            <div
                                                class="absolute -top-20 -right-20 h-56 w-56 rounded-full bg-gradient-to-br from-sky-500/20 via-blue-600/15 to-indigo-600/20 blur-2xl">
                                            </div>
                                            <div
                                                class="absolute -bottom-20 -left-20 h-56 w-56 rounded-full bg-gradient-to-tr from-violet-500/20 via-fuchsia-500/12 to-rose-500/18 blur-2xl">
                                            </div>
                                        </div>

                                        <div
                                            class="relative flex flex-col items-center justify-center text-center gap-3">
                                            <div
                                                class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow ring-1 ring-white/15">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path
                                                        d="M4 3a2 2 0 00-2 2v8a2 2 0 002 2h3l1 1h4l1-1h3a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0015.586 3H4z" />
                                                    <path d="M10 7a3 3 0 100 6 3 3 0 000-6z" />
                                                </svg>
                                            </div>

                                            <div class="space-y-1">
                                                <div class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                    Arrastra y suelta tu imagen aquí
                                                </div>
                                                <div class="text-xs text-neutral-600 dark:text-neutral-400">
                                                    o selecciona un archivo desde tu equipo
                                                </div>
                                            </div>

                                            <div class="w-full max-w-sm">
                                                <input type="file" wire:model="foto" accept="image/*"
                                                    class="block w-full cursor-pointer rounded-2xl border border-neutral-200 bg-white px-4 py-2 text-sm text-neutral-700 shadow-sm
                                                           file:mr-4 file:rounded-xl file:border-0 file:bg-neutral-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white
                                                           hover:file:bg-neutral-800
                                                           dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-200 dark:file:bg-white dark:file:text-neutral-900 dark:hover:file:bg-neutral-200" />
                                                <div class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                                                    Recomendado: 600×600 o mayor.
                                                </div>
                                                <flux:error name="foto" />
                                            </div>

                                            <div wire:loading wire:target="foto"
                                                class="text-xs text-neutral-500 dark:text-neutral-400">
                                                Subiendo foto…
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview -->
                                <div class="lg:col-span-1">
                                    <div
                                        class="rounded-3xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                        <div class="flex items-center justify-between">
                                            <div class="text-sm font-semibold text-neutral-900 dark:text-white">Vista
                                                previa</div>
                                            <span
                                                class="text-[11px] text-neutral-500 dark:text-neutral-400">Auto</span>
                                        </div>

                                        <div class="mt-4">
                                            <div class="mx-auto w-32 sm:w-40">
                                                <div
                                                    class="relative aspect-square overflow-hidden rounded-3xl bg-neutral-100 ring-1 ring-neutral-200 dark:bg-neutral-800/40 dark:ring-neutral-800">
                                                    @if (isset($foto) && $foto)
                                                        <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa"
                                                            class="h-full w-full object-cover" />
                                                    @else
                                                        <div class="grid h-full place-items-center p-2 text-center">
                                                            <div class="space-y-2">
                                                                <div
                                                                    class="mx-auto h-10 w-10 rounded-2xl bg-neutral-900 text-white grid place-items-center dark:bg-white dark:text-neutral-900">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        class="h-5 w-5" viewBox="0 0 20 20"
                                                                        fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414a2 2 0 00-.586-1.414l-2.414-2.414A2 2 0 0015.586 3H4zm6 5a3 3 0 100 6 3 3 0 000-6z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                </div>
                                                                <div
                                                                    class="text-xs font-semibold text-neutral-900 dark:text-white">
                                                                    Sin foto aún
                                                                </div>
                                                                <div
                                                                    class="text-[11px] text-neutral-600 dark:text-neutral-400">
                                                                    Al subir una imagen, aquí verás la vista previa.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 text-xs text-neutral-500 dark:text-neutral-400">
                                            Formatos: JPG, PNG, WEBP. Tamaño sugerido: ≤ 2MB.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end pt-2">
                <flux:button variant="ghost" type="button" wire:click="cancelar">
                    Cancelar
                </flux:button>

                <flux:button type="button" wire:click="guardar" class="w-full sm:w-auto">
                    Guardar
                </flux:button>
            </div>
        </div>
    </div>
</div>
