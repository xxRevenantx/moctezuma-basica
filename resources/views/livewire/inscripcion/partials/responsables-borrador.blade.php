<div class="space-y-5 md:col-span-2 xl:col-span-4">
    <div class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900/40 dark:bg-amber-950/20">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <label for="buscar-tutor-inscripcion" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Buscar responsable existente
                    </label>
                    <span class="text-xs text-slate-500">Nombre, CURP, teléfono o correo</span>
                </div>
                <input id="buscar-tutor-inscripcion" type="search"
                    wire:model.live.debounce.500ms="buscarTutor"
                    autocomplete="off"
                    placeholder="Escribe al menos 2 caracteres"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:ring-amber-950/40">
            </div>

            <button type="button" wire:click="$toggle('mostrarNuevoTutor')"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                <flux:icon.user-plus class="h-4 w-4" />
                Registrar responsable
            </button>
        </div>

        <div wire:loading.flex wire:target="buscarTutor" class="mt-3 items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-300">
            <flux:icon.loader-circle class="h-4 w-4 animate-spin" />
            Buscando en la base de datos…
        </div>

        @if ($resultadosTutores !== [])
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($resultadosTutores as $resultado)
                    <div wire:key="tutor-borrador-resultado-{{ $resultado['id'] }}"
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-800 dark:text-white">{{ $resultado['nombre'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $resultado['curp'] }}</p>
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $resultado['telefono'] ?: 'Sin teléfono' }}
                                    @if ($resultado['correo']) · {{ $resultado['correo'] }} @endif
                                </p>
                                <p class="mt-1 text-xs font-semibold text-sky-700 dark:text-sky-300">
                                    {{ $resultado['relaciones'] }} alumno(s) relacionado(s)
                                </p>
                            </div>
                            <button type="button" wire:click="agregarResponsable({{ $resultado['id'] }})"
                                class="shrink-0 rounded-xl bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-700">
                                Agregar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (mb_strlen(trim($buscarTutor)) >= 2)
            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/70 px-4 py-5 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900/50">
                No se encontraron responsables disponibles. Puedes registrar uno nuevo.
            </div>
        @endif
    </div>

    @if ($mostrarNuevoTutor)
        <div class="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900/40 dark:bg-neutral-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Registrar nuevo responsable</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Los datos personales se guardan una sola vez. El parentesco y las autorizaciones se definen después para este alumno.
                    </p>
                </div>
                <button type="button" wire:click="$set('mostrarNuevoTutor', false)"
                    class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800">
                    <flux:icon.x class="h-5 w-5" />
                </button>
            </div>

            <label class="mt-5 inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                <input type="checkbox" wire:model.live="nuevoTutor.sin_curp"
                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                Responsable extranjero o sin CURP disponible
            </label>

            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @if (!($nuevoTutor['sin_curp'] ?? false))
                    <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                            <div class="flex-1">
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">CURP del responsable</label>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide',
                                        'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300' => $curpNuevoTutorEstado === 'inicial',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' => $curpNuevoTutorEstado === 'disponible',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300' => $curpNuevoTutorEstado === 'encontrada',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' => !in_array($curpNuevoTutorEstado, ['inicial', 'disponible', 'encontrada'], true),
                                    ])>
                                        {{ $curpNuevoTutorEstado === 'disponible' ? 'Disponible' : ($curpNuevoTutorEstado === 'encontrada' ? 'Ya registrado' : 'Validación local') }}
                                    </span>
                                </div>
                                <input type="text" wire:model.blur="nuevoTutor.curp" maxlength="18" autocomplete="off"
                                    x-on:input="$el.value = $el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 18)"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 uppercase tracking-wider dark:border-neutral-700 dark:bg-neutral-950">
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $curpNuevoTutorMensaje }}</p>
                                @error('nuevoTutor.curp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="consultarCurpNuevoTutor" wire:loading.attr="disabled"
                                @disabled(!$curpNuevoTutorValida || $tutorExistenteNuevoTutor)
                                class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span wire:loading.remove wire:target="consultarCurpNuevoTutor">Consultar datos</span>
                                <span wire:loading wire:target="consultarCurpNuevoTutor">Consultando…</span>
                            </button>
                        </div>

                        @if ($tutorExistenteNuevoTutor)
                            <div class="mt-3 flex flex-col gap-3 rounded-xl border border-indigo-200 bg-white p-3 dark:border-indigo-900/50 dark:bg-neutral-900 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-extrabold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Usa el responsable existente</p>
                                    <p class="mt-1 font-bold text-slate-800 dark:text-white">{{ $tutorExistenteNuevoTutor['nombre_completo'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $tutorExistenteNuevoTutor['relaciones_activas'] }} relación(es) activa(s)
                                        · {{ $tutorExistenteNuevoTutor['activo'] ? 'Activo' : 'Archivado' }}
                                    </p>
                                    @if (!$tutorExistenteNuevoTutor['activo'])
                                        <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-300">Reactívalo desde Tutores antes de relacionarlo.</p>
                                    @endif
                                </div>
                                <button type="button" wire:click="usarTutorExistenteNuevoTutor"
                                    @disabled(!$tutorExistenteNuevoTutor['activo'])
                                    class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    Agregar este responsable
                                </button>
                            </div>
                        @endif

                        @if ($alumnoMismaCurpNuevoTutor)
                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-semibold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/25 dark:text-amber-200">
                                Esta CURP también aparece en el alumno {{ $alumnoMismaCurpNuevoTutor['nombre_completo'] }} ({{ $alumnoMismaCurpNuevoTutor['estatus'] }}). La coincidencia se permite, pero debe verificarse.
                            </div>
                        @endif

                        @if ($curpNuevoTutorAdvertencia)
                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-semibold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/25 dark:text-amber-200">
                                {{ $curpNuevoTutorAdvertencia }}
                            </div>
                        @endif

                        @if ($curpNuevoTutorExito)
                            <div class="mt-3 flex flex-col gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs font-semibold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/25 dark:text-emerald-200 sm:flex-row sm:items-center sm:justify-between">
                                <span>{{ $curpNuevoTutorExito }}</span>
                                @if ($diferenciasCurpNuevoTutor !== [])
                                    <button type="button" wire:click="aplicarCurpNuevoTutor(true)" class="rounded-lg border border-emerald-300 px-3 py-1.5 font-bold hover:bg-emerald-100 dark:border-emerald-800 dark:hover:bg-emerald-950/50">
                                        Aplicar todos y reemplazar
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Identificador alternativo</label>
                        <input type="text" wire:model="nuevoTutor.identificador_alternativo" maxlength="80"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 uppercase dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevoTutor.identificador_alternativo') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Motivo por el que no se captura CURP</label>
                        <input type="text" wire:model="nuevoTutor.motivo_sin_curp" maxlength="255"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevoTutor.motivo_sin_curp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @foreach ([
                    'nombre' => 'Nombre(s)',
                    'apellido_paterno' => 'Apellido paterno',
                    'apellido_materno' => 'Apellido materno',
                    'telefono_celular' => 'Teléfono celular',
                    'telefono_casa' => 'Teléfono de casa',
                    'correo_electronico' => 'Correo electrónico',
                    'calle' => 'Calle',
                    'numero' => 'Número',
                    'colonia' => 'Colonia',
                    'codigo_postal' => 'Código postal',
                    'municipio' => 'Municipio',
                    'estado' => 'Estado',
                    'ciudad' => 'Ciudad',
                ] as $campo => $etiqueta)
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $etiqueta }}</label>
                        <input type="{{ $campo === 'correo_electronico' ? 'email' : 'text' }}"
                            wire:model="nuevoTutor.{{ $campo }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevoTutor.' . $campo) <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Género</label>
                    <select wire:model="nuevoTutor.genero"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="">No especificado</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="O">Otro</option>
                    </select>
                    @error('nuevoTutor.genero') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Fecha de nacimiento</label>
                    <input type="date" wire:model="nuevoTutor.fecha_nacimiento"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                    @error('nuevoTutor.fecha_nacimiento') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <button type="button" wire:click="resetNuevoTutor"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                    Limpiar
                </button>
                <button type="button" wire:click="crearTutorBorrador" wire:loading.attr="disabled"
                    class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="crearTutorBorrador">Guardar y agregar</span>
                    <span wire:loading wire:target="crearTutorBorrador">Guardando…</span>
                </button>
            </div>
        </div>
    @endif

    @if ($responsablesMensaje)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ $responsablesMensaje }}
        </div>
    @endif

    @error('responsables')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300">
            {{ $message }}
        </div>
    @enderror

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white">Responsables seleccionados</h3>
                <p class="text-sm text-slate-500">Para menores de edad se requiere al menos un responsable y un contacto principal.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                {{ count($responsables) }} seleccionados
            </span>
        </div>

        @forelse ($responsables as $indice => $responsable)
            <article wire:key="responsable-borrador-{{ $responsable['tutor_id'] }}"
                @class([
                    'rounded-3xl border p-5 shadow-sm',
                    'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/10' => $responsable['es_principal'],
                    'border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' => !$responsable['es_principal'],
                ])>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-extrabold text-slate-800 dark:text-white">{{ $responsable['nombre'] }}</h4>
                            @if ($responsable['es_principal'])
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Principal</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $responsable['curp'] }}</p>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                            {{ $responsable['telefono'] ?: 'Sin teléfono' }}
                            @if ($responsable['correo']) · {{ $responsable['correo'] }} @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if (!$responsable['es_principal'])
                            <button type="button" wire:click="establecerPrincipalBorrador({{ $indice }})"
                                class="rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900/50 dark:bg-neutral-900 dark:text-emerald-300">
                                Hacer principal
                            </button>
                        @endif
                        <button type="button" wire:click="usarDomicilioResponsable({{ $indice }}, 'vacios')"
                            class="rounded-xl border border-sky-200 bg-white px-3 py-2 text-xs font-bold text-sky-700 hover:bg-sky-50 dark:border-sky-900/50 dark:bg-neutral-900 dark:text-sky-300">
                            Completar domicilio
                        </button>
                        <button type="button" wire:click="quitarResponsable({{ $indice }})"
                            class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900/50 dark:bg-neutral-900 dark:text-rose-300">
                            Quitar
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Parentesco</label>
                        <select wire:model="responsables.{{ $indice }}.parentesco"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                            @foreach (\App\Services\GestionResponsablesAlumnoService::PARENTESCOS as $parentesco)
                                <option value="{{ $parentesco }}">{{ $parentesco }}</option>
                            @endforeach
                        </select>
                        @error("responsables.$indice.parentesco") <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($puedeGestionarResponsablesSensibles)
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Acreditación de tutela</label>
                            <select wire:model="responsables.{{ $indice }}.estado_tutela"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                                @foreach (\App\Services\GestionResponsablesAlumnoService::ESTADOS_TUTELA as $estadoTutela)
                                    <option value="{{ $estadoTutela }}">{{ str($estadoTutela)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-500 dark:border-neutral-700 dark:bg-neutral-950">
                            La tutela legal y autorización de recogida requieren un permiso adicional.
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Observaciones de esta relación</label>
                        <input type="text" wire:model="responsables.{{ $indice }}.observaciones" maxlength="1000"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                    </div>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'vive_con_alumno' => 'Vive con el alumno',
                        'recibe_avisos' => 'Recibe avisos',
                        'contacto_emergencia' => 'Contacto de emergencia',
                        'responsable_economico' => 'Responsable económico',
                    ] as $campo => $etiqueta)
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-950 dark:text-slate-200">
                            <input type="checkbox" wire:model="responsables.{{ $indice }}.{{ $campo }}"
                                class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            {{ $etiqueta }}
                        </label>
                    @endforeach

                    @if ($puedeGestionarResponsablesSensibles)
                        @foreach ([
                            'es_tutor_legal' => 'Tutor legal',
                            'recibe_calificaciones' => 'Recibe calificaciones',
                            'autorizado_recoger' => 'Autorizado para recoger',
                        ] as $campo => $etiqueta)
                            <label class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/50 px-3 py-2 text-sm text-indigo-800 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-indigo-200">
                                <input type="checkbox" wire:model="responsables.{{ $indice }}.{{ $campo }}"
                                    class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $etiqueta }}
                            </label>
                        @endforeach
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center dark:border-neutral-700 dark:bg-neutral-900/50">
                <flux:icon.users-round class="mx-auto h-8 w-8 text-slate-400" />
                <p class="mt-3 font-bold text-slate-700 dark:text-slate-200">Aún no has seleccionado responsables</p>
                <p class="mt-1 text-sm text-slate-500">Busca un tutor existente o registra uno nuevo.</p>
            </div>
        @endforelse
    </div>

    @if ($direccionTutorAdvertencia)
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
            {{ $direccionTutorAdvertencia }}
        </p>
    @endif
</div>
