<div x-data="{ show: false, loading: false }" x-cloak x-trap.noscroll="show" x-show="show"
    @abrir-modal-alumnos-tutor.window="show = true; loading = true"
    @gestion-alumnos-tutor-cargada.window="loading = false"
    @cerrar-modal-alumnos-tutor.window="show = false; loading = false; $wire.cerrar()"
    @keydown.escape.window="show = false; loading = false; $wire.cerrar()"
    class="fixed inset-0 z-[60] flex items-center justify-center" aria-live="polite">

    <div class="absolute inset-0 bg-neutral-950/75 backdrop-blur-sm" x-show="show" x-transition.opacity
        @click.self="show = false; loading = false; $wire.cerrar()"></div>

    <section x-show="show" wire:ignore.self role="dialog" aria-modal="true"
        aria-labelledby="gestion-alumnos-tutor-titulo"
        class="relative mx-3 flex max-h-[92vh] w-[96vw] max-w-7xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-neutral-950 dark:ring-white/10"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        <div x-show="loading" class="absolute inset-0 z-30 flex items-center justify-center bg-white/85 backdrop-blur-sm dark:bg-neutral-950/85">
            <div class="rounded-2xl border border-zinc-200 bg-white px-5 py-4 shadow-lg dark:border-zinc-800 dark:bg-neutral-900">
                <div class="flex items-center gap-3 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                    </svg>
                    Cargando relaciones…
                </div>
            </div>
        </div>

        <div class="h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-violet-600"></div>

        <header class="flex items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:px-7">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-600 dark:text-blue-400">Relación tutor–alumno</p>
                <h2 id="gestion-alumnos-tutor-titulo" class="mt-1 truncate text-xl font-bold text-zinc-900 dark:text-white">
                    {{ $tutorNombre ?: 'Administrar alumnos' }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Agrega varios alumnos, configura funciones, conserva historial y sincroniza el contacto principal legado.
                </p>
            </div>

            <button type="button" @click="show = false; loading = false; $wire.cerrar()"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-zinc-500 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                <flux:icon.x class="h-5 w-5" />
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-7">
            @if ($mensaje)
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/25 dark:text-emerald-300">
                    {{ $mensaje }}
                </div>
            @endif

            @error('seleccionados')
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/25 dark:text-rose-300">
                    {{ $message }}
                </div>
            @enderror
            @error('relaciones')
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/25 dark:text-rose-300">
                    {{ $message }}
                </div>
            @enderror

            @if (!$tutorActivo)
                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/25 dark:text-amber-200">
                    Este responsable está archivado. Puedes consultar su historial, pero debes reactivarlo desde el directorio de Tutores para agregar alumnos o reactivar relaciones.
                </div>
            @endif

            @if ($tutorActivo)
            <section class="rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 p-4 shadow-sm dark:border-sky-900/50 dark:from-sky-950/20 dark:via-neutral-950 dark:to-indigo-950/20 sm:p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="font-bold text-zinc-900 dark:text-white">Agregar alumnos</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Por defecto se muestran alumnos activos. Activa el historial para localizar egresados, bajas o registros anteriores.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-bold text-zinc-700 dark:border-zinc-700 dark:bg-neutral-900 dark:text-zinc-200">
                        <input type="checkbox" wire:model.live="incluirHistoricos" class="rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                        Incluir alumnos históricos
                    </label>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-zinc-500">Buscar alumno</label>
                        <input type="search" wire:model.live.debounce.400ms="buscarAlumno"
                            placeholder="Nombre, CURP, matrícula o folio…"
                            class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 dark:border-zinc-700 dark:bg-neutral-900 dark:text-white">
                    </div>
                    <div wire:loading.flex wire:target="buscarAlumno,incluirHistoricos" class="items-center gap-2 pb-3 text-xs font-bold text-sky-700 dark:text-sky-300">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                        </svg>
                        Buscando…
                    </div>
                </div>

                @if ($resultados !== [])
                    <div class="mt-4 grid gap-2 xl:grid-cols-2">
                        @foreach ($resultados as $resultado)
                            <label wire:key="resultado-alumno-tutor-{{ $resultado['id'] }}"
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-3 transition hover:border-sky-300 hover:bg-sky-50/50 dark:border-zinc-700 dark:bg-neutral-900 dark:hover:border-sky-700 dark:hover:bg-sky-950/20">
                                <input type="checkbox" wire:model="seleccionados.{{ $resultado['id'] }}"
                                    @disabled($resultado['relacion_activa'])
                                    class="mt-1 rounded border-zinc-300 text-sky-600 focus:ring-sky-500 disabled:opacity-40">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-zinc-900 dark:text-white">{{ $resultado['nombre'] }}</span>
                                        @if ($resultado['relacion_activa'])
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">YA RELACIONADO</span>
                                        @elseif ($resultado['relacion_historica'])
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">SE REACTIVARÁ</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $resultado['matricula'] }} · {{ $resultado['estatus'] }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $resultado['ubicacion'] }} · {{ $resultado['ciclo'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @elseif (mb_strlen(trim($buscarAlumno)) >= 2)
                    <div class="mt-4 rounded-2xl border border-dashed border-zinc-300 bg-white/70 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-neutral-900/60">
                        No se encontraron alumnos con ese criterio.
                    </div>
                @endif

                <div class="mt-5 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-neutral-900">
                    <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Configuración para los alumnos seleccionados</h4>
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Parentesco</label>
                            <select wire:model="nuevoParentesco" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                @foreach ($parentescos as $parentesco)
                                    <option value="{{ $parentesco }}">{{ $parentesco }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Fecha inicial</label>
                            <input type="date" wire:model="nuevaFechaInicio" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                            @error('nuevaFechaInicio') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        @if ($puedeSensibles)
                            <div>
                                <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Estado de tutela</label>
                                <select wire:model="nuevoEstadoTutela" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                    @foreach ($estadosTutela as $estadoTutela)
                                        <option value="{{ $estadoTutela }}">{{ str($estadoTutela)->replace('_', ' ')->title() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="md:col-span-2 xl:col-span-2">
                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Observaciones</label>
                            <input type="text" wire:model="nuevasObservaciones" maxlength="1000" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'nuevoPrincipal' => 'Contacto principal',
                            'nuevoViveConAlumno' => 'Vive con el alumno',
                            'nuevoRecibeAvisos' => 'Recibe avisos',
                            'nuevoContactoEmergencia' => 'Contacto de emergencia',
                            'nuevoResponsableEconomico' => 'Responsable económico',
                        ] as $campo => $etiqueta)
                            <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                <input type="checkbox" wire:model="{{ $campo }}" class="rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                                {{ $etiqueta }}
                            </label>
                        @endforeach

                        @if ($puedeSensibles)
                            @foreach ([
                                'nuevoTutorLegal' => 'Tutor legal',
                                'nuevoRecibeCalificaciones' => 'Recibe calificaciones',
                                'nuevoAutorizadoRecoger' => 'Autorizado para recoger',
                            ] as $campo => $etiqueta)
                                <label class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/50 px-3 py-2 text-sm text-indigo-800 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-indigo-200">
                                    <input type="checkbox" wire:model="{{ $campo }}" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $etiqueta }}
                                </label>
                            @endforeach
                        @endif
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" wire:click="relacionarSeleccionados"
                            @if ($nuevoPrincipal) wire:confirm="Los alumnos que ya tengan contacto principal conservarán al anterior como secundario. ¿Continuar?" @endif
                            wire:loading.attr="disabled" wire:target="relacionarSeleccionados"
                            class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-sky-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="relacionarSeleccionados">Relacionar seleccionados</span>
                            <span wire:loading wire:target="relacionarSeleccionados">Relacionando…</span>
                        </button>
                    </div>
                </div>
            </section>
            @endif

            <section class="mt-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Relaciones registradas</h3>
                        <p class="mt-1 text-sm text-zinc-500">Edita funciones, cambia el principal, copia domicilio o conserva una relación como histórica.</p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ count($relaciones) }} relación(es)</span>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse ($relaciones as $indice => $relacion)
                        <article wire:key="gestion-tutor-relacion-{{ $relacion['id'] }}"
                            class="overflow-hidden rounded-3xl border bg-white shadow-sm dark:bg-neutral-900 {{ $relacion['activo'] ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-zinc-200 dark:border-zinc-700' }}">
                            <div class="flex flex-col gap-3 border-b border-zinc-100 p-4 dark:border-zinc-800 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-zinc-900 dark:text-white">{{ $relacion['nombre'] }}</h4>
                                        @if ($relacion['es_principal'])
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">PRINCIPAL</span>
                                        @endif
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $relacion['activo'] ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                            {{ $relacion['activo'] ? 'ACTIVA' : 'HISTÓRICA' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $relacion['matricula'] }} · {{ $relacion['estatus'] }} · {{ $relacion['ubicacion'] }} · {{ $relacion['ciclo'] }}</p>
                                    @if (!$relacion['activo'])
                                        <p class="mt-2 text-xs font-semibold text-zinc-500">Finalizó: {{ $relacion['fecha_fin'] ?: 'sin fecha' }} · {{ $relacion['motivo_fin'] ?: 'sin motivo' }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if ($relacion['activo'] && !$relacion['es_principal'])
                                        <button type="button" wire:click="establecerPrincipal({{ $indice }})"
                                            wire:confirm="El contacto principal actual se conservará como secundario. ¿Hacer principal a este responsable?"
                                            class="rounded-xl border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900/50 dark:text-emerald-300">
                                            Hacer principal
                                        </button>
                                    @endif
                                    @if ($relacion['activo'])
                                        <button type="button" wire:click="copiarDomicilio({{ $indice }}, false)"
                                            class="rounded-xl border border-sky-200 px-3 py-2 text-xs font-bold text-sky-700 hover:bg-sky-50 dark:border-sky-900/50 dark:text-sky-300">
                                            Completar domicilio
                                        </button>
                                        <button type="button" wire:click="copiarDomicilio({{ $indice }}, true)"
                                            wire:confirm="Se reemplazarán los datos de domicilio del alumno con los del responsable. ¿Continuar?"
                                            class="rounded-xl border border-amber-200 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50 dark:border-amber-900/50 dark:text-amber-300">
                                            Reemplazar domicilio
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if ($relacion['activo'])
                                <div class="p-4">
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <div>
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Parentesco</label>
                                            <select wire:model="relaciones.{{ $indice }}.parentesco" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                                @foreach ($parentescos as $parentesco)
                                                    <option value="{{ $parentesco }}">{{ $parentesco }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Fecha inicial</label>
                                            <input type="date" wire:model="relaciones.{{ $indice }}.fecha_inicio" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                        </div>
                                        @if ($puedeSensibles)
                                            <div>
                                                <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Estado de tutela</label>
                                                <select wire:model="relaciones.{{ $indice }}.estado_tutela" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                                    @foreach ($estadosTutela as $estadoTutela)
                                                        <option value="{{ $estadoTutela }}">{{ str($estadoTutela)->replace('_', ' ')->title() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        <div class="md:col-span-2 xl:col-span-2">
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Observaciones</label>
                                            <input type="text" wire:model="relaciones.{{ $indice }}.observaciones" maxlength="1000" class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ([
                                            'vive_con_alumno' => 'Vive con el alumno',
                                            'recibe_avisos' => 'Recibe avisos',
                                            'contacto_emergencia' => 'Contacto de emergencia',
                                            'responsable_economico' => 'Responsable económico',
                                        ] as $campo => $etiqueta)
                                            <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                <input type="checkbox" wire:model="relaciones.{{ $indice }}.{{ $campo }}" class="rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                                                {{ $etiqueta }}
                                            </label>
                                        @endforeach
                                        @if ($puedeSensibles)
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

                                    <div class="mt-5 flex flex-col gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800 lg:flex-row lg:items-end lg:justify-between">
                                        <div class="flex-1">
                                            <label class="mb-1 block text-xs font-black uppercase tracking-wide text-zinc-500">Motivo para retirar</label>
                                            <input type="text" wire:model="motivosRetiro.{{ $relacion['id'] }}" maxlength="255" placeholder="Obligatorio al retirar la relación"
                                                class="w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-neutral-950">
                                            @error('motivoRetiro') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="guardarRelacion({{ $indice }})"
                                                class="rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-sky-700">Guardar relación</button>
                                            <button type="button" wire:click="desactivar({{ $indice }})"
                                                wire:confirm="La relación se conservará como histórica. ¿Retirar este responsable del alumno?"
                                                class="rounded-xl border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-300">Retirar relación</button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex justify-end p-4">
                                    <button type="button" wire:click="reactivar({{ $indice }})"
                                        wire:confirm="¿Reactivar esta relación histórica?"
                                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700">Reactivar relación</button>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 px-5 py-10 text-center dark:border-zinc-700 dark:bg-neutral-900/50">
                            <flux:icon.users-round class="mx-auto h-9 w-9 text-zinc-400" />
                            <p class="mt-3 font-bold text-zinc-700 dark:text-zinc-200">Este responsable aún no tiene alumnos relacionados</p>
                            <p class="mt-1 text-sm text-zinc-500">Busca y selecciona uno o varios alumnos en la sección superior.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <footer class="flex justify-end border-t border-zinc-200 px-5 py-4 dark:border-zinc-800 sm:px-7">
            <button type="button" @click="show = false; loading = false; $wire.cerrar()"
                class="rounded-xl border border-zinc-300 bg-white px-5 py-2.5 text-sm font-bold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-neutral-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Cerrar
            </button>
        </footer>
    </section>
</div>
