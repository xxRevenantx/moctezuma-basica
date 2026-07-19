<section class="rounded-3xl border border-violet-200 bg-violet-50/50 p-5 dark:border-violet-900/50 dark:bg-violet-950/15">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-wide text-violet-600 dark:text-violet-300">
                Alumno seleccionado
            </p>

            <h4 class="mt-1 text-lg font-black text-slate-900 dark:text-white">
                {{ $this->nombreAlumno($this->alumnoSeleccionado) }}
            </h4>

            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                {{ $this->alumnoSeleccionado?->matricula }} · {{ $this->alumnoSeleccionado?->curp }}
            </p>

            <p class="mt-2 text-sm font-semibold text-violet-800 dark:text-violet-200">
                Última etapa:
                {{ $this->ultimaTrayectoriaSeleccionada?->nivel?->nombre ?? '—' }} ·
                {{ $this->ultimaTrayectoriaSeleccionada?->grado?->nombre ?? '—' }} ·
                {{ $this->ultimaTrayectoriaSeleccionada?->etiqueta_estatus ?? '—' }}
            </p>
        </div>

        <flux:button
            type="button"
            wire:click="limpiarAlumno"
            variant="ghost"
            icon="x-mark"
        >
            Cambiar alumno
        </flux:button>
    </div>
</section>

<div class="grid gap-5 xl:grid-cols-2">
    <section class="rounded-3xl border border-sky-200 bg-sky-50/40 p-5 dark:border-sky-900/50 dark:bg-sky-950/15">
        <h4 class="mb-4 font-black text-sky-900 dark:text-sky-100">
            1. Tipo y ubicación de ingreso
        </h4>

        <div class="grid gap-3 sm:grid-cols-2">
            <flux:select wire:model.live="tipo_retorno" label="Tipo de retorno">
                <option value="reingreso">Reingreso de exalumno</option>
                <option value="reincorporacion">Reincorporación al mismo nivel</option>
            </flux:select>

            <flux:input wire:model="fecha_ingreso" type="date" label="Fecha de ingreso" />

            <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                <option value="">Selecciona</option>
                @foreach ($ciclosEscolares as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->inicio_anio }}-{{ $item->fin_anio }}{{ $item->es_actual ? ' · Actual' : '' }}
                    </option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="ciclo_id" label="Corte">
                <option value="">Selecciona</option>
                @foreach ($ciclos as $item)
                    <option value="{{ $item->id }}">{{ $item->ciclo }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="nivel_destino_id" label="Nivel">
                <option value="">Selecciona</option>
                @foreach ($niveles as $item)
                    <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="grado_destino_id" label="Grado">
                <option value="">Selecciona</option>
                @foreach ($this->grados as $item)
                    <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="generacion_destino_id" label="Generación activa">
                <option value="">Selecciona</option>
                @foreach ($this->generaciones as $item)
                    <option value="{{ $item->id }}">{{ $item->anio_ingreso }}-{{ $item->anio_egreso }}</option>
                @endforeach
            </flux:select>

            @includeWhen((bool) $this->esBachillerato, 'livewire.accion.partials.reingreso-semestre-select')

            <flux:select wire:model.live="grupo_destino_id" label="Grupo">
                <option value="">Selecciona</option>
                @foreach ($this->grupos as $item)
                    <option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}</option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="matricula"
                label="Matrícula manual"
                placeholder="Vacío = regla automática"
            />

            <flux:select wire:model="usuario_acceso" label="Acceso del alumno">
                <option value="">Preguntar / decidir después</option>
                <option value="1">Mantener o activar</option>
                <option value="0">Desactivar</option>
            </flux:select>
        </div>

        <div class="mt-3">
            <flux:textarea
                wire:model="justificacion"
                label="Justificación"
                rows="2"
                placeholder="Opcional; necesaria cuando el reingreso no sigue la secuencia esperada"
            />
        </div>
    </section>

    <section class="rounded-3xl border border-emerald-200 bg-emerald-50/40 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/15">
        <h4 class="mb-4 font-black text-emerald-900 dark:text-emerald-100">
            2. Procedencia y documentos
        </h4>

        <div class="grid gap-3 sm:grid-cols-2">
            <flux:input wire:model="escuela_procedencia" label="Escuela de procedencia" />
            <flux:input wire:model="cct_procedencia" label="CCT de procedencia" />
            <flux:input wire:model="ciclo_procedencia" label="Ciclo cursado" placeholder="Ej. 2026-2027" />
            <flux:input wire:model="ultimo_grado_procedencia" label="Último grado cursado" />

            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Constancia de traslado con calificaciones (PDF)
                </label>

                <input
                    type="file"
                    wire:model="constancia_traslado_pdf"
                    accept="application/pdf"
                    class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-950"
                />

                <p class="mt-1 text-xs text-slate-500">
                    Máximo 10 MB. También puede entregarse después.
                </p>
            </div>
        </div>

        <div class="mt-3">
            <flux:textarea
                wire:model="observaciones_procedencia"
                label="Observaciones de procedencia"
                rows="2"
            />
        </div>

        <div class="mt-3">
            <flux:checkbox
                wire:model.live="documentacion_pendiente"
                label="Permitir el retorno y marcar la constancia como pendiente"
            />
        </div>
    </section>
</div>

<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
    <h4 class="font-black text-slate-900 dark:text-white">
        3. Resumen antes de confirmar
    </h4>

    <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-slate-50 p-3 dark:bg-neutral-800">
            <p class="text-xs font-bold uppercase text-slate-500">Estado anterior</p>
            <p class="mt-1 font-black">
                {{ $this->ultimaTrayectoriaSeleccionada?->nivel?->nombre ?? '—' }} ·
                {{ $this->ultimaTrayectoriaSeleccionada?->etiqueta_estatus ?? '—' }}
            </p>
        </div>

        <div class="rounded-2xl bg-sky-50 p-3 dark:bg-sky-950/30">
            <p class="text-xs font-bold uppercase text-sky-600">Nueva ubicación</p>
            <p class="mt-1 font-black text-sky-900 dark:text-sky-100">
                {{ $niveles->firstWhere('id', $nivel_destino_id)?->nombre ?? '—' }} ·
                {{ $this->grados->firstWhere('id', $grado_destino_id)?->nombre ?? '—' }}
            </p>
        </div>

        <div class="rounded-2xl bg-emerald-50 p-3 dark:bg-emerald-950/30">
            <p class="text-xs font-bold uppercase text-emerald-600">Generación</p>
            <p class="mt-1 font-black text-emerald-900 dark:text-emerald-100">
                {{ $this->textoGeneracion($this->generacionDestinoSeleccionada) }}
            </p>
        </div>

        <div class="rounded-2xl bg-violet-50 p-3 dark:bg-violet-950/30">
            <p class="text-xs font-bold uppercase text-violet-600">Documento</p>
            <p class="mt-1 font-black text-violet-900 dark:text-violet-100">
                {{ $constancia_traslado_pdf ? 'Adjunto' : ($documentacion_pendiente ? 'Pendiente' : 'No requerido') }}
            </p>
        </div>
    </div>

    <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <flux:checkbox
            wire:model.live="confirmar"
            label="Confirmo que el estado anterior quedará en el historial y se activará la nueva ubicación académica."
        />

        <flux:button
            type="button"
            wire:click="confirmarReingreso"
            wire:loading.attr="disabled"
            variant="primary"
            icon="arrow-path"
            :disabled="! $confirmar"
        >
            Confirmar retorno
        </flux:button>
    </div>
</section>

@includeWhen($errors->any(), 'livewire.accion.partials.reingreso-errores')
@includeWhen((bool) $alumno_reingresado_id, 'livewire.accion.partials.reingreso-calificaciones-externas')
