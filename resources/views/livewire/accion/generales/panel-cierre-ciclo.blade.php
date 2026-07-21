<div class="space-y-6">
    <div class="grid gap-3 md:grid-cols-4">
        @foreach ([1 => 'Diagnóstico', 2 => 'Seleccionar alumnos', 3 => 'Opciones de cierre', 4 => 'Confirmar'] as $numero => $titulo)
            <div class="rounded-2xl border p-4 {{ $paso === $numero ? 'border-sky-400 bg-sky-50 dark:bg-sky-950/20' : 'border-slate-200 dark:border-neutral-700' }}">
                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Paso {{ $numero }}</p>
                <p class="font-black text-slate-900 dark:text-white">{{ $titulo }}</p>
            </div>
        @endforeach
    </div>

    @if ($paso === 1)
        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model.live="generacion_id" label="Generación a revisar">
                <option value="">Selecciona una generación</option>
                @foreach ($generaciones as $generacion)<option value="{{ $generacion->id }}">{{ $generacion->etiqueta }} - {{ $generacion->status ? 'Activa' : 'Inactiva' }}</option>@endforeach
            </flux:select>
            <flux:select wire:model.live="ciclo_escolar_id" label="Ciclo escolar">
                <option value="">Sin ciclo específico</option>
                @foreach ($ciclos as $ciclo)<option value="{{ $ciclo->id }}">{{ $ciclo->nombre }} {{ $ciclo->es_actual ? '(Actual)' : '' }}</option>@endforeach
            </flux:select>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $tarjetas = [
                    ['Calificaciones pendientes', $this->diagnostico['calificacionesPendientes'], 'amber'],
                    ['Promociones sin confirmar', $this->diagnostico['promocionesPendientes'], 'rose'],
                    ['Alumnos sin grupo', $this->diagnostico['sinGrupo'], 'orange'],
                    ['No promovidos', $this->diagnostico['noPromovidos'], 'violet'],
                    ['Bajas pendientes', $this->diagnostico['bajasPendientes'], 'slate'],
                    ['Egresados existentes', $this->diagnostico['egresados'], 'sky'],
                    ['Generaciones vencidas', $this->diagnostico['generacionesVencidas'], 'red'],
                    ['Documentos faltantes', $this->diagnostico['documentosPendientes'], 'emerald'],
                    ['Preinscripciones no formalizadas', $this->diagnostico['preinscripcionesNoFormalizadas'] ?? 0, 'amber'],
                ];
            @endphp
            @foreach ($tarjetas as [$label, $valor, $color])
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $valor }}</p>
                </div>
            @endforeach
        </div>
        <div class="flex justify-end"><flux:button variant="primary" wire:click="preparar" spinner="preparar">Revisar candidatos</flux:button></div>
    @endif

    @if ($paso === 2)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
            Los alumnos con bloqueos se muestran para diagnóstico, pero no pueden seleccionarse. Bajas, traslados, no promovidos y egresados conservan su estado.
        </div>
        <div class="overflow-x-auto rounded-2xl border dark:border-neutral-700">
            <table class="min-w-full text-sm"><thead class="bg-slate-50 dark:bg-neutral-800"><tr><th class="p-3"></th><th class="p-3 text-left">Alumno</th><th class="p-3 text-left">Matrícula</th><th class="p-3 text-left">Ubicación</th><th class="p-3 text-left">Estatus</th><th class="p-3 text-left">Validación</th></tr></thead>
                <tbody class="divide-y dark:divide-neutral-800">@foreach ($this->candidatos as $fila)<tr><td class="p-3"><input type="checkbox" wire:model="seleccionados" value="{{ $fila['id'] }}" @disabled(!$fila['apto'])></td><td class="p-3 font-bold">{{ $fila['nombre'] }}</td><td class="p-3">{{ $fila['matricula'] }}</td><td class="p-3">{{ $fila['ubicacion'] }}</td><td class="p-3">{{ $fila['estatus'] }}</td><td class="p-3"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $fila['apto'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $fila['observacion'] }}</span></td></tr>@endforeach</tbody>
            </table>
        </div>
        @error('seleccionados')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        <div class="flex justify-between"><flux:button wire:click="anterior">Anterior</flux:button><flux:button variant="primary" wire:click="siguiente">Continuar con {{ count($seleccionados) }} alumno(s)</flux:button></div>
    @endif

    @if ($paso === 3)
        <div class="grid gap-4 md:grid-cols-2"><flux:input type="date" wire:model="fecha_egreso" label="Fecha oficial de egreso" /><flux:textarea wire:model="motivo" label="Motivo administrativo" rows="4" /></div>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex gap-3 rounded-2xl border p-4 dark:border-neutral-700"><input type="checkbox" wire:model="cerrar_generacion" class="mt-1"><span><b class="block">Cerrar generación</b><small class="text-slate-500">La conserva para historial, pero la marca como inactiva.</small></span></label>
            <label class="flex gap-3 rounded-2xl border p-4 dark:border-neutral-700"><input type="checkbox" wire:model="cerrar_ciclo" class="mt-1"><span><b class="block">Cerrar ciclo escolar</b><small class="text-slate-500">Úsalo solo cuando todos los niveles y procesos estén terminados.</small></span></label>
        </div>
        <div class="flex justify-between"><flux:button wire:click="anterior">Anterior</flux:button><flux:button variant="primary" wire:click="siguiente">Revisar confirmación</flux:button></div>
    @endif

    @if ($paso === 4)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-900/40 dark:bg-rose-950/20">
            <h3 class="font-black text-rose-800 dark:text-rose-200">Confirmación final</h3>
            <p class="mt-2 text-sm text-rose-700 dark:text-rose-300">Se egresarán {{ count($seleccionados) }} alumnos. Su acceso y matrícula vigente quedarán desactivados. El proceso se registrará alumno por alumno.</p>
        </div>
        <flux:input wire:model="confirmacion" label="Escribe EGRESAR para confirmar" />
        <div class="flex justify-between"><flux:button wire:click="anterior">Anterior</flux:button><flux:button variant="danger" wire:click="ejecutar" spinner="ejecutar">Ejecutar cierre</flux:button></div>
    @endif
</div>
