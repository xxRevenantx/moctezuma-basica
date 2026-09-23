<div class="space-y-5">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <h2 class="text-xl font-bold">PDF combinado de alumnos</h2>
        <p class="mt-1 text-sm text-slate-500">Agrega alumnos de cualquier nivel. La selección y sus copias se conservan al cambiar filtros; cambiar el ciclo escolar inicia una nueva selección.</p>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:select wire:model.live="ciclo" label="Ciclo escolar">
                @foreach ($this->ciclos as $item)
                    <option value="{{ $item->id }}">{{ $item->inicio_anio }}–{{ $item->fin_anio }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="nivel" label="Nivel">
                <option value="">Todos los niveles</option>
                @foreach ($this->niveles as $item)
                    <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="grado" label="Grado">
                <option value="">Todos los grados</option>
                @foreach ($this->grados as $item)
                    <option value="{{ $item->id }}">{{ $item->nivel?->nombre }} · {{ $item->nombre }}°</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="grupo" label="Grupo">
                <option value="">Todos los grupos</option>
                @foreach ($this->grupos as $item)
                    <option value="{{ $item->id }}">{{ $item->grado?->nombre }}° {{ $item->asignacionGrupo?->nombre ?? 'Sin grupo' }} · {{ $item->generacion?->anio_ingreso }}–{{ $item->generacion?->anio_egreso }}{{ $item->semestre ? ' · Sem. ' . ($item->semestre->numero ?? $item->semestre->id) : '' }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.350ms="buscar" label="Buscar alumno" placeholder="Nombre, apellidos, matrícula o CURP" />
            <flux:select wire:model.live="situacion" label="Situación escolar">
                <option value="activos">Activos</option>
                <option value="bajas">Bajas</option>
                <option value="egresados">Egresados</option>
                <option value="todos">Todas las situaciones</option>
            </flux:select>
            <flux:select wire:model.live="copias" label="Copias para nuevos alumnos">
                @for ($i = 1; $i <= $this->maxCopias(); $i++)
                    <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'copia' : 'copias' }}</option>
                @endfor
            </flux:select>
            <div class="flex items-end"><flux:button wire:click="limpiarFiltros">Limpiar filtros</flux:button></div>
        </div>
        <p class="mt-4 text-xs text-slate-500">Para agregar un nivel completo, selecciona únicamente el nivel; para agregar un grupo, selecciona nivel, grado y grupo. “Agregar resultados” incluye todas las páginas y respeta la búsqueda.</p>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-wrap items-center justify-between gap-3 p-5">
            <h3 class="font-bold">Resultados · {{ $resultados->total() }} alumnos</h3>
            <flux:button wire:click="agregarResultados" wire:loading.attr="disabled" :disabled="$resultados->total() === 0">Agregar resultados ({{ $resultados->total() }})</flux:button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 dark:bg-neutral-800 dark:text-slate-300"><tr><th class="px-5 py-3">Alumno</th><th class="px-5 py-3">Nivel</th><th class="px-5 py-3">Grado / grupo</th><th class="px-5 py-3">Situación</th><th class="px-5 py-3">Selección</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($resultados as $alumno)
                        <tr wire:key="resultado-combinado-{{ $alumno->id }}">
                            <td class="px-5 py-3"><p class="font-semibold">{{ $this->nombre($alumno) }}</p><p class="text-xs text-slate-500">{{ $alumno->matricula }}</p></td>
                            <td class="px-5 py-3">{{ $alumno->nivel?->nombre }}</td>
                            <td class="px-5 py-3">{{ $alumno->grado?->nombre }}° {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}</td>
                            <td class="px-5 py-3">{{ ucfirst(str_replace('_', ' ', $alumno->estatus ?? '')) }}</td>
                            <td class="px-5 py-3">
                                @if (isset($seleccion[$alumno->id]))
                                    <flux:button size="sm" wire:click="quitar({{ $alumno->id }})">Quitar de selección</flux:button>
                                @else
                                    <flux:button size="sm" wire:click="agregar({{ $alumno->id }})">Agregar</flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">No hay alumnos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5">{{ $resultados->links() }}</div>
    </section>

    @php
        $listaSeleccionada = $this->seleccionados;
        $totalCopias = $listaSeleccionada->sum(fn ($alumno) => $seleccion[$alumno->id] ?? 0);
    @endphp
    <section class="overflow-hidden rounded-3xl border border-sky-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="space-y-4 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h3 class="text-lg font-bold">Selección para el PDF</h3><p class="text-sm text-slate-500">{{ $listaSeleccionada->count() }} alumnos · {{ $totalCopias }} credenciales · {{ (int) ceil($totalCopias / 4) }} hojas</p></div>
                <div class="flex flex-wrap gap-2">
                    <flux:button wire:click="aplicarCopias" :disabled="$listaSeleccionada->isEmpty()">Aplicar {{ $copias }} copias a todos</flux:button>
                    <flux:button wire:click="limpiarSeleccion" wire:confirm="¿Quitar todos los alumnos de la selección?" :disabled="$listaSeleccionada->isEmpty()">Limpiar selección</flux:button>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($listaSeleccionada->groupBy('nivel.nombre') as $nombreNivel => $alumnosNivel)
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800 dark:bg-sky-950 dark:text-sky-200">{{ $nombreNivel }}: {{ $alumnosNivel->count() }} alumnos</span>
                @endforeach
            </div>
            <p class="text-xs text-slate-500">Orden: nivel, grado, grupo y apellidos. Se aprovecha la misma hoja entre niveles y se conserva el diseño de las credenciales.</p>
            @error('seleccion') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="button" wire:click="descargar" wire:loading.attr="disabled"
                @disabled($listaSeleccionada->isEmpty())
                @if ($totalCopias >= (int) config('credenciales.umbral_confirmacion_copias', 100))
                    wire:confirm="Se generarán {{ $totalCopias }} credenciales. ¿Continuar?"
                @endif
                class="cursor-pointer rounded-xl bg-sky-700 px-5 py-3 text-sm font-bold text-white hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="descargar">Descargar PDF combinado</span>
                <span wire:loading wire:target="descargar">Generando PDF…</span>
            </button>
        </div>
        <div class="max-h-96 overflow-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 dark:bg-neutral-800 dark:text-slate-300"><tr><th class="px-5 py-3">Alumno</th><th class="px-5 py-3">Nivel / grado / grupo</th><th class="px-5 py-3">Copias</th><th class="px-5 py-3">Acción</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($listaSeleccionada as $alumno)
                        <tr wire:key="seleccion-combinada-{{ $alumno->id }}">
                            <td class="px-5 py-3"><p class="font-semibold">{{ $this->nombre($alumno) }}</p><p class="text-xs text-slate-500">{{ $alumno->matricula }}</p></td>
                            <td class="px-5 py-3">{{ $alumno->nivel?->nombre }} · {{ $alumno->grado?->nombre }}° {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}</td>
                            <td class="px-5 py-3"><div class="flex items-center gap-3">
                                <flux:button size="sm" aria-label="Reducir copias" wire:click="ajustarCopias({{ $alumno->id }}, -1)" :disabled="$seleccion[$alumno->id] <= 1">−</flux:button>
                                <span>{{ $seleccion[$alumno->id] }}</span>
                                <flux:button size="sm" aria-label="Aumentar copias" wire:click="ajustarCopias({{ $alumno->id }}, 1)" :disabled="$seleccion[$alumno->id] >= $this->maxCopias()">+</flux:button>
                            </div></td>
                            <td class="px-5 py-3"><flux:button size="sm" wire:click="quitar({{ $alumno->id }})">Quitar</flux:button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-slate-500">Agrega alumnos desde la lista de resultados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
