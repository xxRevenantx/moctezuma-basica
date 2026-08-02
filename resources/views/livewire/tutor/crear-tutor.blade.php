<div>

    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nuevo Tutor
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra una persona responsable. El parentesco se define al relacionarla con cada alumno.
        </p>
    </div>

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle (form-pro) -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-nivel"
            class="group inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                   bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400
                   dark:focus:ring-offset-neutral-900">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/15">
                <!-- ícono lápiz -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M5 19h4l10-10-4-4L5 15v4m14.7-11.3a1 1 0 000-1.4l-2-2a1 1 0 00-1.4 0l-1.6 1.6 3.4 3.4 1.6-1.6z" />
                </svg>
            </span>
            <span class="font-medium">{{ __('Nuevo Tutor') }}</span>
            <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5l-6-6h12l-6 6z" />
                </svg>
            </span>
        </button>

        <!-- Panel (form-pro) -->
        <div id="panel-nivel" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">
            <div class="mx-auto w-full">
                <div
                    class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    {{-- Acento superior --}}
                    <div class="h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                    {{-- Toolbar --}}
                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Nuevo tutor
                            </h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Captura los datos propios de la persona. El parentesco y las autorizaciones no se guardan aquí.
                            </p>
                        </div>


                    </div>

                    {{-- Form --}}
                    <form wire:submit.prevent="guardar" class="p-5 pt-0">
                        {{-- Loader overlay --}}
                        <div wire:loading.flex wire:target="guardar"
                            class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-950/70">
                            <div
                                class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z">
                                    </path>
                                </svg>
                                <span class="text-sm text-zinc-700 dark:text-zinc-200">Guardando…</span>
                            </div>
                        </div>

                        {{-- Identidad --}}
                        <div
                            class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Identidad</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">La CURP se valida localmente. También se admiten responsables extranjeros o sin CURP.</p>
                                </div>

                                <span
                                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                                    Campos clave
                                </span>
                            </div>

                            {{-- Consulta de CURP con el mismo servicio utilizado en Inscripción --}}
                            <div class="mt-4 overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 shadow-sm dark:border-sky-900/50 dark:from-sky-950/25 dark:via-zinc-950 dark:to-indigo-950/20">
                                <div class="flex flex-col gap-3 border-b border-sky-100 p-4 dark:border-sky-900/40 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M9 12l2 2 4-4" />
                                                <path d="M12 3 4.5 6v5.5c0 4.7 3.2 8.9 7.5 9.9 4.3-1 7.5-5.2 7.5-9.9V6L12 3Z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-zinc-900 dark:text-zinc-100">Consulta y autollenado por CURP</h4>
                                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">Mismo servicio de Inscripción</span>
                                            </div>
                                            <p class="mt-1 max-w-3xl text-xs leading-5 text-zinc-600 dark:text-zinc-400">
                                                Primero se valida la CURP en la base local. Si no existe, el servicio completa nombre, apellidos, género, fecha y estado de nacimiento sin reemplazar lo que ya capturaste.
                                            </p>
                                        </div>
                                    </div>

                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-bold',
                                        'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $curp_estado === 'inicial',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' => in_array($curp_estado, ['incompleta', 'invalida', 'error'], true),
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' => $curp_estado === 'disponible',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' => $curp_estado === 'encontrada',
                                    ])>
                                        {{ match($curp_estado) { 'disponible' => 'Disponible', 'encontrada' => 'Ya registrada', 'incompleta' => 'Incompleta', 'invalida' => 'No válida', 'error' => 'Error', default => 'Pendiente' } }}
                                    </span>
                                </div>

                                <div class="grid gap-3 p-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                    <flux:field>
                                        <flux:label badge="Requerido">CURP del responsable *</flux:label>
                                        <flux:input wire:model.blur="curp" maxlength="18" inputmode="text" autocomplete="off" spellcheck="false"
                                            :disabled="$sin_curp"
                                            x-on:input="$el.value = $el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 18)"
                                            class="uppercase tracking-wider" placeholder="18 caracteres" />
                                        <flux:error name="curp" />
                                        @if (!$sin_curp)
                                            <p @class([
                                                'mt-1 text-xs font-semibold',
                                                'text-zinc-500 dark:text-zinc-400' => $curp_estado === 'inicial',
                                                'text-amber-700 dark:text-amber-300' => in_array($curp_estado, ['incompleta', 'invalida', 'error'], true),
                                                'text-emerald-700 dark:text-emerald-300' => $curp_estado === 'disponible',
                                                'text-indigo-700 dark:text-indigo-300' => $curp_estado === 'encontrada',
                                            ])>{{ $curp_mensaje }}</p>
                                        @endif
                                    </flux:field>

                                    <flux:button type="button" variant="primary" wire:click="consultarCurp"
                                        wire:loading.attr="disabled" wire:target="consultarCurp,curp"
                                        :disabled="$sin_curp || !$curp_local_validada || $curp_existe_local"
                                        class="rounded-xl bg-sky-600 hover:bg-sky-700">
                                        <span wire:loading.remove wire:target="consultarCurp">Consultar datos</span>
                                        <span wire:loading wire:target="consultarCurp" class="inline-flex items-center gap-2">
                                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                                            </svg>
                                            Consultando…
                                        </span>
                                    </flux:button>
                                </div>

                                @if ($tutor_existente)
                                    <div class="mx-4 mb-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-xs font-black uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Responsable ya registrado</p>
                                                <p class="mt-1 font-bold text-zinc-900 dark:text-zinc-100">{{ $tutor_existente['nombre_completo'] }}</p>
                                                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $tutor_existente['curp'] }} · {{ $tutor_existente['relaciones_activas'] }} relación(es) activa(s)
                                                    · {{ $tutor_existente['activo'] ? 'Activo' : 'Archivado' }}
                                                    @if ($tutor_existente['telefono']) · {{ $tutor_existente['telefono'] }} @endif
                                                </p>
                                                @if (!$tutor_existente['activo'])
                                                    <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-300">Reactívalo desde el directorio de Tutores para agregar o reactivar relaciones.</p>
                                                @endif
                                            </div>
                                            <flux:button type="button" variant="primary" wire:click="administrarTutorExistente" class="rounded-xl">
                                                {{ $tutor_existente['activo'] ? 'Administrar alumnos' : 'Ver historial' }}
                                            </flux:button>
                                        </div>
                                    </div>
                                @endif

                                @if ($alumno_con_misma_curp)
                                    <div class="mx-4 mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                        <strong>Coincidencia permitida:</strong> esta CURP también aparece en el alumno {{ $alumno_con_misma_curp['nombre_completo'] }}
                                        ({{ $alumno_con_misma_curp['matricula'] ?: 'sin matrícula' }} · {{ $alumno_con_misma_curp['estatus'] }}). Verifica que se trate de la misma persona antes de guardar.
                                    </div>
                                @endif

                                @if ($curp_advertencia)
                                    <div class="mx-4 mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                        {{ $curp_advertencia }}
                                    </div>
                                @endif

                                @if ($curp_exito)
                                    <div class="mx-4 mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <span>{{ $curp_exito }}</span>
                                            @if (!empty($curp_diferencias))
                                                <flux:button type="button" size="sm" variant="outline" wire:click="aplicarDatosCurp(true)" class="rounded-xl">
                                                    Aplicar todos y reemplazar
                                                </flux:button>
                                            @endif
                                        </div>
                                        @if (!empty($curp_diferencias))
                                            <details class="mt-3 rounded-lg border border-emerald-200/70 bg-white/70 p-3 dark:border-emerald-900/50 dark:bg-zinc-950/40">
                                                <summary class="cursor-pointer text-xs font-bold">Revisar {{ count($curp_diferencias) }} diferencia(s)</summary>
                                                <div class="mt-2 space-y-2 text-xs">
                                                    @foreach ($curp_diferencias as $diferencia)
                                                        <p><strong>{{ str($diferencia['campo'])->replace('_', ' ')->title() }}:</strong> capturado “{{ $diferencia['actual'] }}” · servicio “{{ $diferencia['externo'] }}”</p>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div class="sm:col-span-2 space-y-3">
                                    <label class="inline-flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                                        <input type="checkbox" wire:model.live="sin_curp" class="rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                                        Responsable extranjero o sin CURP disponible
                                    </label>

                                    @if ($sin_curp)
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <flux:field>
                                                <flux:label badge="Requerido">Identificador alternativo *</flux:label>
                                                <flux:input wire:model="identificador_alternativo" maxlength="80" class="uppercase" />
                                                <flux:error name="identificador_alternativo" />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label badge="Requerido">Motivo sin CURP *</flux:label>
                                                <flux:input wire:model="motivo_sin_curp" maxlength="255" />
                                                <flux:error name="motivo_sin_curp" />
                                            </flux:field>
                                        </div>
                                    @endif

                                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-semibold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                        El parentesco se captura en la relación con cada alumno, porque una misma persona puede ser madre de un alumno y tía de otro.
                                    </div>
                                </div>

                                <flux:field>
                                    <flux:label badge="Requerido">Género *</flux:label>
                                    <flux:select wire:model="genero">
                                        <option value="">Selecciona…</option>
                                        <option value="M">M - Masculino</option>
                                        <option value="F">F - Femenino</option>
                                        <option value="O">O - Otro</option>
                                    </flux:select>
                                    <flux:error name="genero" />
                                </flux:field>
                            </div>
                        </div>

                        {{-- Secciones colapsables --}}
                        <div class="mt-4 space-y-4" x-data="{ gen: true, dom: false, con: false }"
                            x-on:tutor-curp-aplicada.window="gen = true">
                            {{-- DATOS GENERALES --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
                                    @click="gen=!gen">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos
                                            generales
                                        </h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Nombre completo y
                                            datos de
                                            nacimiento.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-zinc-500 transition" :class="gen ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="gen" x-transition.opacity.duration.200ms class="p-4 pt-0"
                                    style="display:none;">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label badge="Requerido">Nombre *</flux:label>
                                            <flux:input wire:model="nombre" class="uppercase"
                                                placeholder="Ej. CARLOS ALBERTO" />
                                            <flux:error name="nombre" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Apellido paterno *</flux:label>
                                            <flux:input wire:model="apellido_paterno" class="uppercase"
                                                placeholder="Ej. NÚÑEZ" />
                                            <flux:error name="apellido_paterno" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Apellido materno</flux:label>
                                            <flux:input wire:model="apellido_materno" class="uppercase"
                                                placeholder="Ej. PÉREZ (opcional)" />
                                            <flux:error name="apellido_materno" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Fecha de nacimiento</flux:label>
                                            <flux:input type="date" wire:model="fecha_nacimiento"
                                                placeholder="Selecciona una fecha" />
                                            <flux:error name="fecha_nacimiento" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Ciudad nacimiento</flux:label>
                                            <flux:input wire:model="ciudad_nacimiento"
                                                placeholder="Ej. CD ALTAMIRANO" />
                                            <flux:error name="ciudad_nacimiento" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Municipio nacimiento</flux:label>
                                            <flux:input wire:model="municipio_nacimiento"
                                                placeholder="Ej. PUNGARABATO" />
                                            <flux:error name="municipio_nacimiento" />
                                        </flux:field>

                                        <flux:field class="sm:col-span-3">
                                            <flux:label badge="Opcional">Estado nacimiento</flux:label>
                                            <flux:input wire:model="estado_nacimiento" placeholder="Ej. GUERRERO" />
                                            <flux:error name="estado_nacimiento" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>

                            {{-- DOMICILIO --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
                                    @click="dom=!dom">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Domicilio
                                        </h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Calle, colonia,
                                            municipio y
                                            CP.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-zinc-500 transition" :class="dom ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="dom" x-transition.opacity.duration.200ms class="p-4 pt-0"
                                    style="display:none;">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field class="sm:col-span-2">
                                            <flux:label badge="Requerido">Calle *</flux:label>
                                            <flux:input wire:model="calle"
                                                placeholder="Ej. FRANCISCO I. MADERO ORIENTE" />
                                            <flux:error name="calle" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Número</flux:label>
                                            <flux:input wire:model="numero" placeholder="Ej. 800 / S/N" />
                                            <flux:error name="numero" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Colonia *</flux:label>
                                            <flux:input wire:model="colonia" placeholder="Ej. ESQUIPULA" />
                                            <flux:error name="colonia" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Ciudad *</flux:label>
                                            <flux:input wire:model="ciudad" placeholder="Ej. CD ALTAMIRANO" />
                                            <flux:error name="ciudad" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Municipio *</flux:label>
                                            <flux:input wire:model="municipio" placeholder="Ej. PUNGARABATO" />
                                            <flux:error name="municipio" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Estado *</flux:label>
                                            <flux:input wire:model="estado" placeholder="Ej. GUERRERO" />
                                            <flux:error name="estado" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Código postal *</flux:label>
                                            <flux:input wire:model="codigo_postal" inputmode="numeric"
                                                placeholder="Ej. 40662" />
                                            <flux:error name="codigo_postal" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>

                            {{-- CONTACTO --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
                                    @click="con=!con">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Contacto
                                        </h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Teléfonos y correo
                                            electrónico.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-zinc-500 transition" :class="con ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="con" x-transition.opacity.duration.200ms class="p-4 pt-0"
                                    style="display:none;">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label badge="Opcional">Teléfono casa</flux:label>
                                            <flux:input wire:model="telefono_casa" inputmode="tel"
                                                placeholder="Ej. 767 688 0000" />
                                            <flux:error name="telefono_casa" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Teléfono celular</flux:label>
                                            <flux:input wire:model="telefono_celular" inputmode="tel"
                                                placeholder="Ej. 767 123 4567" />
                                            <flux:error name="telefono_celular" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Correo</flux:label>
                                            <flux:input type="email" wire:model="correo_electronico"
                                                placeholder="correo@dominio.com" />
                                            <flux:error name="correo_electronico" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Relación opcional con alumnos --}}
                        <div class="mt-4 overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm dark:border-violet-900/50 dark:bg-zinc-950">
                            <div class="border-b border-violet-100 bg-gradient-to-r from-violet-50 to-indigo-50 p-4 dark:border-violet-900/40 dark:from-violet-950/20 dark:to-indigo-950/20">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Alumnos relacionados</h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Opcional. Busca y selecciona uno o varios alumnos; cada relación conserva su parentesco y autorizaciones.</p>
                                    </div>
                                    <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950/60 dark:text-violet-300">
                                        {{ count($alumnos_relacionar) }} seleccionado(s)
                                    </span>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                    <flux:field>
                                        <flux:label>Buscar alumno</flux:label>
                                        <flux:input wire:model.live.debounce.350ms="buscar_alumno" placeholder="Nombre, matrícula, folio o CURP" />
                                        <flux:error name="buscar_alumno" />
                                    </flux:field>
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-2.5 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                                        <input type="checkbox" wire:model.live="incluir_alumnos_historicos" class="rounded border-zinc-300 text-violet-600 focus:ring-violet-500">
                                        Incluir egresados, bajas e históricos
                                    </label>
                                </div>

                                @if (!empty($resultados_alumnos))
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($resultados_alumnos as $resultado)
                                            <button type="button" wire:click="seleccionarAlumno({{ $resultado['id'] }})"
                                                class="rounded-xl border border-zinc-200 p-3 text-left transition hover:border-violet-300 hover:bg-violet-50 dark:border-zinc-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/20">
                                                <span class="block font-semibold text-zinc-900 dark:text-zinc-100">{{ $resultado['nombre'] }}</span>
                                                <span class="mt-1 block text-xs text-zinc-500">{{ $resultado['matricula'] }} · {{ $resultado['ubicacion'] }}</span>
                                                <span class="mt-1 block text-[11px] font-bold {{ $resultado['activo_listas'] ? 'text-emerald-600' : 'text-amber-600' }}">{{ $resultado['estatus'] }} · {{ $resultado['ciclo'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif (mb_strlen(trim($buscar_alumno)) >= 2)
                                    <p class="mt-3 rounded-xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700">No se encontraron alumnos con ese criterio.</p>
                                @endif

                                @if (!empty($alumnos_relacionar))
                                    <div class="mt-4 space-y-4">
                                        @foreach ($alumnos_relacionar as $indice => $relacion)
                                            <div wire:key="nuevo-tutor-alumno-{{ $relacion['inscripcion_id'] }}" class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div>
                                                        <p class="font-bold text-zinc-900 dark:text-zinc-100">{{ $relacion['nombre'] }}</p>
                                                        <p class="mt-1 text-xs text-zinc-500">{{ $relacion['matricula'] }} · {{ $relacion['ubicacion'] }} · {{ $relacion['ciclo'] }}</p>
                                                        @if ($relacion['reemplazara_principal'] && $relacion['es_principal'])
                                                            <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">Al guardar, el responsable principal actual quedará como secundario.</p>
                                                        @endif
                                                    </div>
                                                    <button type="button" wire:click="quitarAlumno({{ $indice }})" class="rounded-lg px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">Quitar</button>
                                                </div>

                                                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                    <flux:field>
                                                        <flux:label>Parentesco *</flux:label>
                                                        <flux:select wire:model="alumnos_relacionar.{{ $indice }}.parentesco">
                                                            @foreach ($parentescos as $parentesco)
                                                                <option value="{{ $parentesco }}">{{ str($parentesco)->replace('_', ' ')->title() }}</option>
                                                            @endforeach
                                                        </flux:select>
                                                        <flux:error name="alumnos_relacionar.{{ $indice }}.parentesco" />
                                                    </flux:field>
                                                    <flux:field>
                                                        <flux:label>Inicio de relación *</flux:label>
                                                        <flux:input type="date" wire:model="alumnos_relacionar.{{ $indice }}.fecha_inicio" />
                                                        <flux:error name="alumnos_relacionar.{{ $indice }}.fecha_inicio" />
                                                    </flux:field>
                                                    <flux:field class="md:col-span-2">
                                                        <flux:label>Observaciones</flux:label>
                                                        <flux:input wire:model="alumnos_relacionar.{{ $indice }}.observaciones" maxlength="1000" />
                                                        <flux:error name="alumnos_relacionar.{{ $indice }}.observaciones" />
                                                    </flux:field>
                                                </div>

                                                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                                    @foreach ([
                                                        'es_principal' => 'Principal',
                                                        'vive_con_alumno' => 'Vive con el alumno',
                                                        'recibe_avisos' => 'Recibe avisos',
                                                        'contacto_emergencia' => 'Emergencia',
                                                        'responsable_economico' => 'Responsable económico',
                                                    ] as $campo => $etiqueta)
                                                        <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
                                                            <input type="checkbox" wire:model.live="alumnos_relacionar.{{ $indice }}.{{ $campo }}" class="rounded border-zinc-300 text-violet-600 focus:ring-violet-500">
                                                            {{ $etiqueta }}
                                                        </label>
                                                    @endforeach

                                                    @if ($puede_relaciones_sensibles)
                                                        @foreach ([
                                                            'es_tutor_legal' => 'Tutor legal',
                                                            'recibe_calificaciones' => 'Recibe calificaciones',
                                                            'autorizado_recoger' => 'Autorizado para recoger',
                                                        ] as $campo => $etiqueta)
                                                            <label class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-indigo-200">
                                                                <input type="checkbox" wire:model.live="alumnos_relacionar.{{ $indice }}.{{ $campo }}" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                                                {{ $etiqueta }}
                                                            </label>
                                                        @endforeach
                                                    @endif
                                                </div>

                                                @if ($puede_relaciones_sensibles && $relacion['es_tutor_legal'])
                                                    <div class="mt-3 max-w-sm">
                                                        <flux:field>
                                                            <flux:label>Estado de tutela</flux:label>
                                                            <flux:select wire:model="alumnos_relacionar.{{ $indice }}.estado_tutela">
                                                                @foreach ($estadosTutela as $estadoTutela)
                                                                    <option value="{{ $estadoTutela }}">{{ str($estadoTutela)->replace('_', ' ')->title() }}</option>
                                                                @endforeach
                                                            </flux:select>
                                                        </flux:field>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <label class="mt-4 inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <input type="checkbox" wire:model="abrir_gestion_despues" class="rounded border-zinc-300 text-violet-600 focus:ring-violet-500">
                                    Abrir el administrador de relaciones después de guardar
                                </label>
                            </div>
                        </div>

                        {{-- Acciones finales --}}
                        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <flux:button type="button" variant="outline" wire:click="limpiar" class="rounded-xl">
                                Cancelar
                            </flux:button>

                            @php
                                $requiereConfirmacionPrincipal = collect($alumnos_relacionar ?? [])->contains(
                                    fn ($item) => ($item['es_principal'] ?? false)
                                        && ($item['reemplazara_principal'] ?? false)
                                );
                            @endphp

                            @if ($requiereConfirmacionPrincipal)
                                <flux:button type="submit" variant="primary"
                                    wire:confirm="Uno o más alumnos ya tienen un contacto principal. El anterior se conservará como secundario. ¿Continuar?"
                                    class="rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 hover:brightness-110"
                                    wire:loading.attr="disabled" wire:target="guardar">
                                    <span wire:loading.remove wire:target="guardar">Guardar tutor</span>
                                    <span wire:loading wire:target="guardar">Guardando…</span>
                                </flux:button>
                            @else
                                <flux:button type="submit" variant="primary"
                                    class="rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 hover:brightness-110"
                                    wire:loading.attr="disabled" wire:target="guardar">
                                    <span wire:loading.remove wire:target="guardar">Guardar tutor</span>
                                    <span wire:loading wire:target="guardar">Guardando…</span>
                                </flux:button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
