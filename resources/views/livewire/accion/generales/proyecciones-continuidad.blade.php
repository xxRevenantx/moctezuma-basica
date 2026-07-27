<div class="space-y-5">
    @php
        $conteosProyeccion = $this->conteos;
        $gruposDisponibles = $this->gruposDisponibles;
        $estilosEstado = [
            'pendiente' => 'bg-amber-100 text-amber-800',
            'confirmada' => 'bg-emerald-100 text-emerald-800',
            'cancelada' => 'bg-slate-200 text-slate-700',
        ];
    @endphp

    <section
        class="overflow-hidden rounded-3xl border border-indigo-200 bg-white dark:border-indigo-900/50 dark:bg-neutral-900">
        <div
            class="border-b border-indigo-100 bg-gradient-to-r from-indigo-50 via-sky-50 to-white p-5 dark:border-indigo-900/50 dark:from-indigo-950/30 dark:via-sky-950/20 dark:to-neutral-900 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600">Continuidad provisional</p>
                    <h3 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Confirmación de alumnos para el
                        siguiente nivel</h3>
                    <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Los alumnos pendientes ya quedaron como <b>egresados</b> del nivel de origen. Todavía no son
                        alumnos activos del nivel destino.
                        Confirma únicamente a quienes realmente regresaron; quienes no continúen conservarán su egreso y
                        no quedarán registrados como baja.
                    </p>
                </div>
                <div class="grid min-w-[300px] grid-cols-3 gap-2 text-center">
                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-950/20">
                        <p class="text-2xl font-black text-amber-700 dark:text-amber-300">
                            {{ $conteosProyeccion['pendiente'] }}</p>
                        <p class="text-[11px] font-black uppercase tracking-wide text-amber-700">Pendientes</p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">
                            {{ $conteosProyeccion['confirmada'] }}</p>
                        <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700">Confirmados</p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-neutral-700 dark:bg-neutral-800">
                        <p class="text-2xl font-black text-slate-700 dark:text-slate-200">
                            {{ $conteosProyeccion['cancelada'] }}</p>
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-600">No continúan</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4 p-5 sm:p-6">
            <div class="grid gap-3 lg:grid-cols-4">
                <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar alumno"
                    placeholder="Nombre, matrícula o CURP" />
                <flux:select wire:model.live="filtro_estado" label="Estado de la proyección">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="pendiente">Pendiente de confirmar</flux:select.option>
                    <flux:select.option value="confirmada">Continuidad confirmada</flux:select.option>
                    <flux:select.option value="cancelada">No continuará</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="filtro_ciclo_destino_id" label="Ciclo destino">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($ciclosDestino as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex items-end gap-2">
                    <flux:button class="flex-1" wire:click="seleccionarPendientesVisibles">Seleccionar pendientes
                    </flux:button>
                    <flux:button variant="ghost" wire:click="limpiarSeleccion">Limpiar</flux:button>
                </div>
            </div>

            @error('seleccion_proyecciones')
                <p class="rounded-xl bg-rose-50 p-3 text-sm font-bold text-rose-700 dark:bg-rose-950/20 dark:text-rose-300">
                    {{ $message }}</p>
            @enderror

            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                <p class="text-sm text-indigo-900 dark:text-indigo-100">
                    Hay <b>{{ count($seleccionados) }}</b> proyección(es) seleccionada(s). La confirmación crea el ciclo
                    destino, activa al alumno y formaliza su matrícula.
                </p>
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="primary" wire:click="prepararConfirmacion"
                        :disabled="count($seleccionados) === 0">
                        Confirmar continuidad
                    </flux:button>
                    <flux:button variant="danger" wire:click="prepararCancelacion"
                        :disabled="count($seleccionados) === 0">
                        No continuarán
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-slate-200 dark:border-neutral-800">
            <table class="min-w-[1250px] w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-800">
                    <tr>
                        <th class="p-3"></th>
                        <th class="p-3 text-left">Alumno</th>
                        <th class="p-3 text-left">Egreso de origen</th>
                        <th class="p-3 text-left">Destino proyectado</th>
                        <th class="p-3 text-left">Grupo para confirmar</th>
                        <th class="p-3 text-left">Matrícula sugerida</th>
                        <th class="p-3 text-center">Estado</th>
                        <th class="p-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($this->proyecciones as $proyeccion)
                        @php
                            $alumno = $proyeccion->inscripcion;
                            $origen = $proyeccion->inscripcionCicloOrigen;
                            $esPendiente = $proyeccion->estado === 'pendiente';
                            $grupos = $gruposDisponibles[$proyeccion->id] ?? [];
                        @endphp
                        <tr wire:key="proyeccion-continuidad-{{ $proyeccion->id }}"
                            class="align-top hover:bg-slate-50/70 dark:hover:bg-neutral-800/40">
                            <td class="p-3">
                                <flux:checkbox wire:model.live="seleccionados" value="{{ $proyeccion->id }}"
                                    :disabled="! $esPendiente" />
                            </td>
                            <td class="p-3">
                                <p class="font-black text-slate-900 dark:text-white">
                                    {{ trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? 'Alumno')) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ $origen?->matricula ?? $alumno?->matricula }}
                                    · {{ $alumno?->curp }}</p>
                                <p class="mt-2 text-xs font-semibold text-violet-700 dark:text-violet-300">Estatus
                                    actual: {{ ucfirst(str_replace('_', ' ', $alumno?->estatus ?? 'egresado')) }}</p>
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                <p class="font-bold">{{ $origen?->nivel?->nombre }}</p>
                                <p>{{ $origen?->grado?->nombre }}{{ $origen?->semestre?->numero ? ' · Semestre ' . $origen->semestre->numero : '' }}
                                </p>
                                <p>Grupo {{ $origen?->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}</p>
                                <p class="mt-1 text-xs">
                                    {{ $origen?->cicloEscolar?->inicio_anio }}-{{ $origen?->cicloEscolar?->fin_anio }}
                                    · Egresado</p>
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                <p class="font-bold">{{ $proyeccion->nivelDestino?->nombre }}</p>
                                <p>{{ $proyeccion->gradoDestino?->nombre }}{{ $proyeccion->semestreDestino?->numero ? ' · Semestre ' . $proyeccion->semestreDestino->numero : '' }}
                                </p>
                                <p>{{ $proyeccion->generacionDestino?->etiqueta }}</p>
                                <p class="mt-1 text-xs">
                                    {{ $proyeccion->cicloDestino?->inicio_anio }}-{{ $proyeccion->cicloDestino?->fin_anio }}
                                </p>
                            </td>
                            <td class="p-3">
                                @if ($esPendiente)
                                    <flux:select wire:model="datos.{{ $proyeccion->id }}.grupo_destino_id"
                                        size="sm" placeholder="Selecciona grupo">
                                        @foreach ($grupos as $grupo)
                                            <flux:select.option value="{{ $grupo['id'] }}">
                                                {{ $grupo['label'] }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @if ($grupos === [])
                                        <p class="mt-1 text-xs font-bold text-rose-600">No hay grupos compatibles
                                            activos.</p>
                                    @endif
                                    @error("datos.{$proyeccion->id}.grupo_destino_id")
                                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                @else
                                    <span
                                        class="font-semibold">{{ $proyeccion->grupoDestino?->asignacionGrupo?->nombre ?? 'No aplica' }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($esPendiente)
                                    <flux:input type="text" wire:model="datos.{{ $proyeccion->id }}.matricula"
                                        size="sm" class:input="uppercase" placeholder="Se generará al confirmar" />
                                    <p class="mt-1 text-xs text-slate-500">Puede modificarse antes de formalizar.</p>
                                @else
                                    <span
                                        class="font-semibold">{{ $proyeccion->matricula_sugerida ?: 'No aplica' }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $estilosEstado[$proyeccion->estado] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $proyeccion->etiqueta_estado }}
                                </span>
                                @if ($proyeccion->estado === 'cancelada' && $proyeccion->motivo_cancelacion)
                                    <p class="mt-2 max-w-xs text-left text-xs text-slate-500">
                                        {{ $proyeccion->motivo_cancelacion }}</p>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @if ($esPendiente)
                                    <div class="flex justify-end gap-2">
                                        <flux:button wire:key="confirmar-proyeccion-{{ $proyeccion->id }}" size="sm"
                                            variant="primary" :loading="false"
                                            wire:click="confirmarUna({{ $proyeccion->id }})"
                                            wire:target="confirmarUna({{ $proyeccion->id }})" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="confirmarUna({{ $proyeccion->id }})">
                                                Confirmar
                                            </span>
                                            <span wire:loading wire:target="confirmarUna({{ $proyeccion->id }})"
                                                class="inline-flex items-center gap-1.5">
                                                <flux:icon name="loading" class="size-4" />
                                                Abriendo...
                                            </span>
                                        </flux:button>
                                        <flux:button wire:key="cancelar-proyeccion-{{ $proyeccion->id }}" size="sm"
                                            variant="danger" :loading="false"
                                            wire:click="cancelarUna({{ $proyeccion->id }})"
                                            wire:target="cancelarUna({{ $proyeccion->id }})" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="cancelarUna({{ $proyeccion->id }})">
                                                No continuará
                                            </span>
                                            <span wire:loading wire:target="cancelarUna({{ $proyeccion->id }})"
                                                class="inline-flex items-center gap-1.5">
                                                <flux:icon name="loading" class="size-4" />
                                                Abriendo...
                                            </span>
                                        </flux:button>
                                    </div>
                                @elseif ($proyeccion->estado === 'confirmada')
                                    <p class="text-xs font-semibold text-emerald-700">Confirmada
                                        {{ $proyeccion->confirmada_at?->format('d/m/Y H:i') }}</p>
                                @else
                                    <p class="text-xs font-semibold text-slate-500">Cancelada
                                        {{ $proyeccion->cancelada_at?->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center text-slate-500">
                                No hay proyecciones que coincidan con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($modalConfirmar)
        <div wire:key="modal-confirmar-proyecciones"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Confirmar continuidad</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Se crearán los registros del ciclo destino y los alumnos seleccionados pasarán de egresados a
                    activos. Esta acción no modifica el egreso histórico del nivel anterior.
                </p>
                <div class="mt-5 space-y-4">
                    <flux:input type="date" wire:model="fecha_confirmacion" label="Fecha efectiva de ingreso" />
                    <flux:textarea wire:model="motivo_confirmacion" label="Motivo de confirmación" rows="4" />
                    <flux:input type="password" wire:model="password_confirmacion_proyeccion"
                        label="Contraseña del usuario" autocomplete="current-password" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('modalConfirmar', false)">Cancelar</flux:button>
                    <flux:button variant="primary" wire:click="confirmarSeleccionadas"
                        spinner="confirmarSeleccionadas">Confirmar seleccionados</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($modalCancelar)
        <div wire:key="modal-cancelar-proyecciones"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Marcar que no continuarán</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    La proyección será cancelada. Los alumnos permanecerán como egresados del nivel y ciclo de origen;
                    no se les registrará una baja en el nivel destino.
                </p>
                <div class="mt-5 space-y-4">
                    <flux:textarea wire:model="motivo_cancelacion" label="Motivo de cancelación" rows="4"
                        placeholder="Motivo de cancelación (obligatorio). Ejemplo: la familia confirmó que continuará sus estudios en otra institución." />
                    <flux:input type="password" wire:model="password_cancelacion_proyeccion"
                        label="Contraseña del usuario" autocomplete="current-password" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('modalCancelar', false)">Volver</flux:button>
                    <flux:button variant="danger" wire:click="cancelarSeleccionadas" spinner="cancelarSeleccionadas">
                        Confirmar que no continuarán</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
