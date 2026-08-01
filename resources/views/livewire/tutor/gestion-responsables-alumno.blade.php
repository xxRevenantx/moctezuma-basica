<div class="space-y-5" wire:key="gestion-responsables-{{ $inscripcionId }}">
    <div class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900/40 dark:bg-amber-950/20">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <label for="buscar-responsable-{{ $inscripcionId }}" class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Buscar responsable existente
                    </label>
                    <span class="text-xs text-slate-500">Nombre, CURP, teléfono o correo</span>
                </div>
                <input id="buscar-responsable-{{ $inscripcionId }}" type="search"
                    wire:model.live.debounce.500ms="buscar"
                    autocomplete="off"
                    placeholder="Escribe al menos 2 caracteres"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:ring-amber-950/40">
            </div>

            @if ($puedeCrearResponsables)
                <button type="button" wire:click="$toggle('mostrarNuevo')"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                    <flux:icon.user-plus class="h-4 w-4" />
                    Registrar responsable
                </button>
            @endif
        </div>

        <div wire:loading.flex wire:target="buscar" class="mt-3 items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-300">
            <flux:icon.loader-circle class="h-4 w-4 animate-spin" />
            Buscando responsables…
        </div>

        @if ($resultados !== [])
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($resultados as $resultado)
                    <div wire:key="resultado-tutor-{{ $resultado['id'] }}"
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-800 dark:text-white">{{ $resultado['nombre'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $resultado['curp'] }}</p>
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $resultado['telefono'] ?: 'Sin teléfono' }}
                                    @if ($resultado['correo'])
                                        · {{ $resultado['correo'] }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs font-semibold text-sky-700 dark:text-sky-300">
                                    {{ $resultado['alumnos'] }} relación(es) activa(s)
                                </p>
                            </div>
                            <button type="button" wire:click="agregarTutor({{ $resultado['id'] }})"
                                class="shrink-0 rounded-xl bg-sky-600 px-3 py-2 text-xs font-bold text-white hover:bg-sky-700">
                                Agregar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (mb_strlen(trim($buscar)) >= 2)
            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/70 px-4 py-5 text-center text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-900/50">
                No se encontraron responsables disponibles. Puedes registrar uno nuevo.
            </div>
        @endif
    </div>

    @if ($mostrarNuevo && $puedeCrearResponsables)
        <div class="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900/40 dark:bg-neutral-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white">Nuevo responsable</h3>
                    <p class="mt-1 text-sm text-slate-500">Sus datos personales se guardan una sola vez; el parentesco se configura por alumno.</p>
                </div>
                <button type="button" wire:click="$set('mostrarNuevo', false)" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-neutral-800">
                    <flux:icon.x-mark class="h-5 w-5" />
                </button>
            </div>

            <label class="mt-5 inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                <input type="checkbox" wire:model.live="nuevo.sin_curp" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                Responsable extranjero o sin CURP disponible
            </label>

            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @if (!($nuevo['sin_curp'] ?? false))
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">CURP</label>
                        <input type="text" wire:model.live.debounce.500ms="nuevo.curp" maxlength="18" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 uppercase dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevo.curp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Identificador alternativo</label>
                        <input type="text" wire:model="nuevo.identificador_alternativo" maxlength="80" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 uppercase dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevo.identificador_alternativo') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Motivo por el que no se captura CURP</label>
                        <input type="text" wire:model="nuevo.motivo_sin_curp" maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevo.motivo_sin_curp') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
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
                        <input type="{{ $campo === 'correo_electronico' ? 'email' : 'text' }}" wire:model="nuevo.{{ $campo }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        @error('nuevo.' . $campo) <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Género</label>
                    <select wire:model="nuevo.genero" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        <option value="">No especificado</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                        <option value="O">Otro</option>
                    </select>
                    @error('nuevo.genero') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700 dark:text-slate-200">Fecha de nacimiento</label>
                    <input type="date" wire:model="nuevo.fecha_nacimiento" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                    @error('nuevo.fecha_nacimiento') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <button type="button" wire:click="resetNuevo" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                    Limpiar
                </button>
                <button type="button" wire:click="crearTutor" wire:loading.attr="disabled"
                    class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="crearTutor">Guardar y relacionar</span>
                    <span wire:loading wire:target="crearTutor">Guardando…</span>
                </button>
            </div>
        </div>
    @endif

    @if ($mensaje)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ $mensaje }}
        </div>
    @endif

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white">Responsables relacionados</h3>
                <p class="text-sm text-slate-500">El parentesco y los permisos pertenecen a esta relación, no al registro general del tutor.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                {{ collect($relaciones)->where('activo', true)->count() }} activos
            </span>
        </div>

        @forelse ($relaciones as $indice => $relacion)
            <article wire:key="relacion-responsable-{{ $relacion['id'] }}"
                @class([
                    'rounded-3xl border p-5 shadow-sm',
                    'border-emerald-200 bg-emerald-50/40 dark:border-emerald-900/40 dark:bg-emerald-950/10' => $relacion['activo'] && $relacion['es_principal'],
                    'border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' => $relacion['activo'] && !$relacion['es_principal'],
                    'border-slate-200 bg-slate-50 opacity-80 dark:border-neutral-800 dark:bg-neutral-900/50' => !$relacion['activo'],
                ])>
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-extrabold text-slate-800 dark:text-white">{{ $relacion['nombre'] }}</h4>
                            @if ($relacion['es_principal'] && $relacion['activo'])
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Principal</span>
                            @endif
                            @if (!$relacion['activo'])
                                <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide text-slate-600 dark:bg-neutral-800 dark:text-slate-300">Histórico</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $relacion['curp'] }}</p>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                            {{ $relacion['telefono'] ?: 'Sin teléfono' }}
                            @if ($relacion['correo']) · {{ $relacion['correo'] }} @endif
                        </p>
                        @if (!$relacion['activo'])
                            <p class="mt-2 text-xs text-slate-500">
                                Finalizó: {{ $relacion['fecha_fin'] ?: 'sin fecha' }} · {{ $relacion['motivo_fin'] ?: 'sin motivo' }}
                            </p>
                        @endif
                    </div>

                    @if ($relacion['activo'])
                        <div class="flex flex-wrap gap-2">
                            @if (!$relacion['es_principal'])
                                <button type="button" wire:click="establecerPrincipal({{ $indice }})"
                                    class="rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900/50 dark:bg-neutral-900 dark:text-emerald-300">
                                    Hacer principal
                                </button>
                            @endif
                            <button type="button" wire:click="usarDomicilio({{ $indice }})"
                                class="rounded-xl border border-sky-200 bg-white px-3 py-2 text-xs font-bold text-sky-700 hover:bg-sky-50 dark:border-sky-900/50 dark:bg-neutral-900 dark:text-sky-300">
                                Usar domicilio
                            </button>
                        </div>
                    @endif
                </div>

                @if ($relacion['activo'])
                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Parentesco</label>
                            <select wire:model="relaciones.{{ $indice }}.parentesco" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                                @foreach ($parentescos as $parentesco)
                                    <option value="{{ $parentesco }}">{{ $parentesco }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($puedeGestionarResponsablesSensibles)
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Acreditación de tutela</label>
                                <select wire:model="relaciones.{{ $indice }}.estado_tutela" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                                    @foreach ($estadosTutela as $estadoTutela)
                                        <option value="{{ $estadoTutela }}">{{ str($estadoTutela)->replace('_', ' ')->title() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-500 dark:border-neutral-700 dark:bg-neutral-950">
                                Los datos de tutela y recogida están protegidos por permisos.
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Observaciones de la relación</label>
                            <input type="text" wire:model="relaciones.{{ $indice }}.observaciones" maxlength="1000" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
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
                                <input type="checkbox" wire:model="relaciones.{{ $indice }}.{{ $campo }}" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
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
                                    <input type="checkbox" wire:model="relaciones.{{ $indice }}.{{ $campo }}" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $etiqueta }}
                                </label>
                            @endforeach
                        @endif
                    </div>

                    <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-neutral-800 lg:flex-row lg:items-end lg:justify-between">
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Motivo para retirar la relación</label>
                            <input type="text" wire:model="motivosRetiro.{{ $relacion['id'] }}" maxlength="255" placeholder="Se solicita únicamente al retirar"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                            @error('motivoRetiro') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="guardarRelacion({{ $indice }})"
                                class="rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-sky-700">
                                Guardar relación
                            </button>
                            <button type="button" wire:click="desactivar({{ $indice }})"
                                class="rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900/50 dark:bg-neutral-900 dark:text-rose-300">
                                Retirar del alumno
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-4 flex justify-end">
                        <button type="button" wire:click="reactivar({{ $indice }})"
                            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700">
                            Reactivar relación
                        </button>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center dark:border-neutral-700 dark:bg-neutral-900/50">
                <flux:icon.users-round class="mx-auto h-8 w-8 text-slate-400" />
                <p class="mt-3 font-bold text-slate-700 dark:text-slate-200">Aún no hay responsables relacionados</p>
                <p class="mt-1 text-sm text-slate-500">Busca un tutor existente o registra uno nuevo.</p>
            </div>
        @endforelse
    </div>
</div>
