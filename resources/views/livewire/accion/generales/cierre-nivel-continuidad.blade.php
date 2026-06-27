<div class="space-y-5" wire:key="cierre-nivel-continuidad-{{ $slug_nivel }}">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-400 to-[#88AC2E]"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.22em] text-[#006492] dark:text-sky-300">Cierre de nivel</p>
                    <h3 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Egreso, continuidad, traslado y repetición</h3>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
                        Cierra la etapa anterior sin borrar el historial. Cuando el alumno continúa aquí, se crea una trayectoria nueva con la matrícula correspondiente al nivel de destino.
                    </p>
                </div>
                <div class="rounded-2xl bg-sky-50 px-4 py-3 text-xs font-bold text-sky-800 dark:bg-sky-950/30 dark:text-sky-200">
                    Solo administrador · operación auditada
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-3xl border border-sky-200 bg-sky-50/50 p-5 dark:border-sky-900/50 dark:bg-sky-950/15">
            <h4 class="mb-4 font-black text-sky-900 dark:text-sky-100">1. Grupo de origen</h4>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:select wire:model.live="ciclo_escolar_origen_id" label="Ciclo escolar">
                    <option value="">Selecciona</option>
                    @foreach ($ciclosEscolares as $item)
                        <option value="{{ $item->id }}">{{ $item->inicio_anio }}-{{ $item->fin_anio }}{{ $item->es_actual ? ' · Actual' : '' }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="ciclo_id_origen" label="Corte">
                    <option value="">Selecciona</option>
                    @foreach ($ciclos as $item)<option value="{{ $item->id }}">{{ $item->ciclo }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="grado_origen_id" label="Grado">
                    <option value="">Selecciona</option>
                    @foreach ($this->gradosOrigen as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="generacion_origen_id" label="Generación">
                    <option value="">Selecciona</option>
                    @foreach ($this->generacionesOrigen as $item)
                        <option value="{{ $item->id }}">{{ $item->anio_ingreso }}-{{ $item->anio_egreso }}{{ !$item->status ? ' · Cerrada' : '' }}</option>
                    @endforeach
                </flux:select>
                @if ($this->esBachilleratoOrigen)
                    <flux:select wire:model.live="semestre_origen_id" label="Semestre">
                        <option value="">Selecciona</option>
                        @foreach ($this->semestresOrigen as $item)<option value="{{ $item->id }}">Semestre {{ $item->numero }}</option>@endforeach
                    </flux:select>
                @endif
                <flux:select wire:model.live="grupo_origen_id" label="Grupo">
                    <option value="">Selecciona</option>
                    @foreach ($this->gruposOrigen as $item)<option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}</option>@endforeach
                </flux:select>
            </div>
        </section>

        <section class="rounded-3xl border border-emerald-200 bg-emerald-50/50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/15">
            <h4 class="mb-4 font-black text-emerald-900 dark:text-emerald-100">2. Destino para continuidad o repetición</h4>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:select wire:model.live="ciclo_escolar_destino_id" label="Ciclo escolar destino">
                    <option value="">Selecciona</option>
                    @foreach ($ciclosEscolares as $item)<option value="{{ $item->id }}">{{ $item->inicio_anio }}-{{ $item->fin_anio }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="ciclo_id_destino" label="Corte destino">
                    <option value="">Selecciona</option>
                    @foreach ($ciclos as $item)<option value="{{ $item->id }}">{{ $item->ciclo }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="nivel_destino_id" label="Nivel destino">
                    <option value="">Selecciona</option>
                    @foreach ($niveles as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="grado_destino_id" label="Grado destino">
                    <option value="">Selecciona</option>
                    @foreach ($this->gradosDestino as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                </flux:select>
                <flux:select wire:model.live="generacion_destino_id" label="Generación destino">
                    <option value="">Selecciona</option>
                    @foreach ($this->generacionesDestino as $item)<option value="{{ $item->id }}">{{ $item->anio_ingreso }}-{{ $item->anio_egreso }}</option>@endforeach
                </flux:select>
                @if ($this->esBachilleratoDestino)
                    <flux:select wire:model.live="semestre_destino_id" label="Semestre destino">
                        <option value="">Selecciona</option>
                        @foreach ($this->semestresDestino as $item)<option value="{{ $item->id }}">Semestre {{ $item->numero }}</option>@endforeach
                    </flux:select>
                @endif
                <flux:select wire:model.live="grupo_destino_id" label="Grupo destino">
                    <option value="">Selecciona</option>
                    @foreach ($this->gruposDestino as $item)<option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}</option>@endforeach
                </flux:select>
            </div>
        </section>
    </div>

    <section class="rounded-3xl border border-amber-200 bg-amber-50/40 p-5 dark:border-amber-900/50 dark:bg-amber-950/15">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <flux:select wire:model.live="accion_global" label="Decisión masiva">
                <option value="continua_institucion">Continúa en el siguiente nivel aquí</option>
                <option value="egresa_otra_escuela">Egresó y se va a otra escuela</option>
                <option value="egresa_sin_destino">Egresó sin destino especificado</option>
                <option value="traslado">Traslado antes de concluir</option>
                <option value="baja_definitiva">Baja definitiva</option>
                <option value="repite">Repite el último grado</option>
            </flux:select>
            <flux:input wire:model="fecha" type="date" label="Fecha efectiva" />
            <flux:select wire:model="usuario_acceso" label="Acceso del alumno">
                <option value="">Decidir después</option>
                <option value="1">Mantener / activar</option>
                <option value="0">Desactivar</option>
            </flux:select>
            <flux:input wire:model="motivo" label="Motivo" placeholder="Opcional" />
            <div class="flex items-end">
                <flux:button type="button" wire:click="aplicarAccionGlobal" variant="primary" class="w-full">Aplicar a seleccionados</flux:button>
            </div>
        </div>
        <div class="mt-3"><flux:textarea wire:model="observaciones" label="Observaciones generales" rows="2" /></div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between dark:border-neutral-700">
            <div class="flex items-center gap-4">
                <flux:checkbox wire:model.live="seleccionar_todos" label="Seleccionar todos" />
                <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ count($seleccionados) }} seleccionados</span>
            </div>
            <div class="w-full md:max-w-md"><flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass" placeholder="Buscar nombre, matrícula o CURP" /></div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1120px] w-full text-sm">
                <thead class="bg-slate-900 text-left text-xs uppercase text-white">
                    <tr><th class="px-4 py-3">Sel.</th><th class="px-4 py-3">Alumno</th><th class="px-4 py-3">Matrícula</th><th class="px-4 py-3">CURP</th><th class="px-4 py-3">Ubicación</th><th class="px-4 py-3">Decisión individual</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($this->alumnos as $trayectoria)
                        @php
                            $alumno = $trayectoria->inscripcion;
                        @endphp
                        <tr wire:key="cierre-{{ $trayectoria->id }}" class="hover:bg-sky-50/50 dark:hover:bg-sky-950/10">
                            <td class="px-4 py-4"><input type="checkbox" wire:model.live="seleccionados" value="{{ $trayectoria->id }}" class="rounded border-slate-300 text-[#006492]" /></td>
                            <td class="px-4 py-4 font-black text-slate-900 dark:text-white">{{ $this->nombreAlumno($alumno) }}</td>
                            <td class="px-4 py-4">{{ $alumno?->matriculasAlumno?->firstWhere('nivel_id', $trayectoria->nivel_id)?->matricula ?: $alumno?->matricula }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $alumno?->curp ?: '—' }}</td>
                            <td class="px-4 py-4">{{ $trayectoria->grado?->nombre }} · {{ $this->textoGrupo($trayectoria->grupo) }} @if($trayectoria->semestre) / Sem. {{ $trayectoria->semestre->numero }} @endif</td>
                            <td class="px-4 py-4">
                                <select wire:model="acciones.{{ $trayectoria->id }}" class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-950">
                                    <option value="">Usar decisión masiva</option>
                                    <option value="continua_institucion">Continúa aquí</option>
                                    <option value="egresa_otra_escuela">Egresó · otra escuela</option>
                                    <option value="egresa_sin_destino">Egresó · sin destino</option>
                                    <option value="traslado">Traslado</option>
                                    <option value="baja_definitiva">Baja definitiva</option>
                                    <option value="repite">Repite</option>
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-14 text-center text-slate-500">Completa los filtros para mostrar alumnos activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200">
            @foreach ($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <flux:checkbox wire:model.live="confirmar" label="Confirmo que revisé el destino y las decisiones. El historial anterior no se eliminará." />
            <flux:button type="button" wire:click="procesar" wire:loading.attr="disabled" variant="primary" icon="check-circle" :disabled="! $confirmar || count($seleccionados) === 0">
                Procesar cierre de nivel
            </flux:button>
        </div>
    </section>

    @if ($generacion_sugerida_cierre_id)
        <section class="rounded-3xl border border-violet-200 bg-violet-50 p-5 dark:border-violet-900/50 dark:bg-violet-950/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><h4 class="font-black text-violet-900 dark:text-violet-100">La generación ya no tiene alumnos activos</h4><p class="text-sm text-violet-700 dark:text-violet-200">Puedes cerrarla para ocultarla de los módulos operativos sin perder su historial.</p></div>
                <flux:button type="button" wire:click="cerrarGeneracionSugerida" variant="primary" icon="archive-box">Cerrar generación</flux:button>
            </div>
        </section>
    @endif
</div>
