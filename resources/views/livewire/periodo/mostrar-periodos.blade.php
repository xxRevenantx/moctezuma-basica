<div x-data="{
    filtrosAbiertos: true,
    eliminar(id) {
        Swal.fire({
            title: '¿Eliminar periodo?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#006492',
            cancelButtonColor: '#dc2626',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((resultado) => resultado.isConfirmed && @this.call('eliminar', id));
    }
}" class="space-y-6">

    <section
        class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-cyan-500 to-[#88AC2E]"></div>

        <div class="p-5 pt-7 sm:p-7 sm:pt-8">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-cyan-500 text-white shadow-lg shadow-sky-500/20">
                        <flux:icon.calendar-days class="h-7 w-7" />
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Periodos
                                académicos</h2>
                            <span
                                class="rounded-full bg-[#88AC2E]/15 px-3 py-1 text-xs font-bold text-[#5f7e13] dark:text-lime-300">
                                {{ $resumen['total'] }} registros
                            </span>
                        </div>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Consulta, filtra y administra los periodos de educación básica y bachillerato desde una sola
                            vista.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                        <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $resumen['total'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 dark:border-sky-900/60 dark:bg-sky-950/30">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-300">Básica
                        </p>
                        <p class="mt-1 text-xl font-black text-sky-800 dark:text-sky-200">{{ $resumen['basica'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                        <p
                            class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">
                            Bachillerato</p>
                        <p class="mt-1 text-xl font-black text-emerald-800 dark:text-emerald-200">
                            {{ $resumen['bachillerato'] }}</p>
                    </div>
                    <div
                        class="rounded-2xl border border-lime-200 bg-lime-50 px-4 py-3 dark:border-lime-900/60 dark:bg-lime-950/30">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-lime-700 dark:text-lime-300">
                            Vigentes</p>
                        <p class="mt-1 text-xl font-black text-lime-800 dark:text-lime-200">{{ $resumen['vigentes'] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div wire:loading.flex
            wire:target="search,nivelFiltro,cicloFiltro,tipoFiltro,generacionFiltro,semestreFiltro,periodoFiltro,estadoFechasFiltro,porPagina,limpiarFiltros,eliminar"
            class="absolute inset-0 z-30 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-slate-950/75">
            <div
                class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-sky-200 border-t-[#006492]"></span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Actualizando periodos...</span>
            </div>
        </div>

        <div class="border-b border-slate-200 p-5 dark:border-slate-800 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="w-full lg:max-w-2xl">
                    <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass"
                        placeholder="Buscar nivel, ciclo, mes, parcial, generación o fecha..." />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="filtrosAbiertos = !filtrosAbiertos"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <flux:icon.funnel class="h-4 w-4" />
                        Filtros
                        <flux:icon.chevron-down class="h-4 w-4 transition"
                            x-bind:class="filtrosAbiertos && 'rotate-180'" />
                    </button>

                    <button type="button" wire:click="limpiarFiltros"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                        <flux:icon.arrow-path class="h-4 w-4" />
                        Limpiar
                    </button>
                </div>
            </div>

            <div x-show="filtrosAbiertos" x-collapse class="pt-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <flux:select wire:model.live="nivelFiltro" label="Nivel educativo">
                        <flux:select.option value="">Todos los niveles</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="cicloFiltro" label="Ciclo escolar">
                        <flux:select.option value="">Todos los ciclos</flux:select.option>
                        @foreach ($ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }} -
                                {{ $ciclo->fin_anio }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="tipoFiltro" label="Tipo de periodo">
                        <flux:select.option value="">Todos los tipos</flux:select.option>
                        <flux:select.option value="basica">Educación básica</flux:select.option>
                        <flux:select.option value="bachillerato">Bachillerato</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="estadoFechasFiltro" label="Estado por fechas">
                        <flux:select.option value="">Todos los estados</flux:select.option>
                        <flux:select.option value="vigente">Vigente</flux:select.option>
                        <flux:select.option value="proximo">Próximo</flux:select.option>
                        <flux:select.option value="finalizado">Finalizado</flux:select.option>
                        <flux:select.option value="sin_fechas">Sin fechas completas</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="generacionFiltro" label="Generación">
                        <flux:select.option value="">Todas las generaciones</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">{{ $generacion->anio_ingreso }} -
                                {{ $generacion->anio_egreso }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="semestreFiltro" label="Semestre">
                        <flux:select.option value="">Todos los semestres</flux:select.option>
                        @foreach ($semestres as $semestre)
                            <flux:select.option value="{{ $semestre->id }}">{{ $semestre->numero }}° semestre
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="periodoFiltro" label="Periodo / Parcial">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($periodosBasica as $periodoBasica)
                            <flux:select.option value="basica:{{ $periodoBasica->id }}">Básica ·
                                {{ $periodoBasica->descripcion }}</flux:select.option>
                        @endforeach
                        @foreach ($parciales as $parcial)
                            <flux:select.option value="bachillerato:{{ $parcial->id }}">Bachillerato ·
                                {{ $parcial->descripcion }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="porPagina" label="Registros por página">
                        <flux:select.option value="10">10 registros</flux:select.option>
                        <flux:select.option value="20">20 registros</flux:select.option>
                        <flux:select.option value="50">50 registros</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6" wire:loading.class="opacity-50"
            wire:target="search,nivelFiltro,cicloFiltro,tipoFiltro,generacionFiltro,semestreFiltro,periodoFiltro,estadoFechasFiltro,porPagina">
            @if ($periodos->isEmpty())
                <div
                    class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-800/40">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800">
                        <flux:icon.calendar-days class="h-7 w-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">No se encontraron periodos</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Modifica los filtros o registra un
                        periodo nuevo.</p>
                    <button type="button" wire:click="limpiarFiltros"
                        class="mt-4 text-sm font-bold text-[#006492] hover:underline dark:text-sky-300">Restablecer
                        filtros</button>
                </div>
            @else
                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 lg:block">
                    <div class="max-h-[68vh] overflow-auto">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-[#006492] text-white shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Periodo
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Nivel y
                                        ciclo</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">
                                        Configuración</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Fechas
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Estado
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">
                                        Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                @foreach ($periodos as $periodo)
                                    @php
                                        $esBachillerato = ($periodo->nivel->slug ?? '') === 'bachillerato';
                                        $mes = $esBachillerato
                                            ? $periodo->mesesBachillerato->meses ?? 'Sin mes'
                                            : $periodo->mesesBasica->meses ?? 'Sin mes';
                                        $nombre = $esBachillerato
                                            ? $periodo->parcialBachillerato->descripcion ?? 'Sin parcial'
                                            : $periodo->periodoBasica->descripcion ?? 'Sin periodo';
                                        $inicio = $periodo->fecha_inicio
                                            ? \Carbon\Carbon::parse($periodo->fecha_inicio)
                                            : null;
                                        $fin = $periodo->fecha_fin ? \Carbon\Carbon::parse($periodo->fecha_fin) : null;
                                        $hoy = now()->startOfDay();
                                        $estado =
                                            !$inicio || !$fin
                                                ? 'Sin fechas'
                                                : ($hoy->lt($inicio)
                                                    ? 'Próximo'
                                                    : ($hoy->gt($fin)
                                                        ? 'Finalizado'
                                                        : 'Vigente'));
                                        $estadoClases = match ($estado) {
                                            'Vigente'
                                                => 'bg-lime-100 text-lime-800 dark:bg-lime-950/50 dark:text-lime-300',
                                            'Próximo' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300',
                                            'Finalizado'
                                                => 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                            default
                                                => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                                        };
                                    @endphp
                                    <tr class="transition hover:bg-sky-50/60 dark:hover:bg-sky-950/20">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <span
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $esBachillerato ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' }}">
                                                    <flux:icon.calendar class="h-5 w-5" />
                                                </span>
                                                <div>
                                                    <p class="font-bold text-slate-900 dark:text-white">
                                                        {{ $nombre }}</p>
                                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $mes }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">
                                                {{ $periodo->nivel->nombre ?? 'Sin nivel' }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ciclo
                                                {{ $periodo->cicloEscolar->inicio_anio ?? '—' }} -
                                                {{ $periodo->cicloEscolar->fin_anio ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if ($esBachillerato)
                                                <p class="font-semibold text-slate-800 dark:text-slate-100">
                                                    {{ $periodo->semestre->numero ?? '—' }}° semestre</p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Generación
                                                    {{ $periodo->generacion->anio_ingreso ?? '—' }} -
                                                    {{ $periodo->generacion->anio_egreso ?? '—' }}</p>
                                            @else
                                                <span
                                                    class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900">Educación
                                                    básica</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <p class="font-medium text-slate-700 dark:text-slate-200">
                                                {{ $inicio?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">al
                                                {{ $fin?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-center"><span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $estadoClases }}">{{ $estado }}</span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-center gap-2">
                                                <button type="button" title="Editar periodo"
                                                    @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $periodo->id }} })"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 transition hover:bg-amber-200 dark:bg-amber-950/50 dark:text-amber-300">
                                                    <flux:icon.pencil-square class="h-4 w-4" />
                                                </button>
                                                <button type="button" title="Eliminar periodo"
                                                    @click="eliminar({{ $periodo->id }})"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-100 text-red-700 transition hover:bg-red-200 dark:bg-red-950/50 dark:text-red-300">
                                                    <flux:icon.trash class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:hidden sm:grid-cols-2">
                    @foreach ($periodos as $periodo)
                        @php
                            $esBachillerato = ($periodo->nivel->slug ?? '') === 'bachillerato';
                            $mes = $esBachillerato
                                ? $periodo->mesesBachillerato->meses ?? 'Sin mes'
                                : $periodo->mesesBasica->meses ?? 'Sin mes';
                            $nombre = $esBachillerato
                                ? $periodo->parcialBachillerato->descripcion ?? 'Sin parcial'
                                : $periodo->periodoBasica->descripcion ?? 'Sin periodo';
                        @endphp
                        <article
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="h-1 {{ $esBachillerato ? 'bg-emerald-500' : 'bg-sky-500' }}"></div>
                            <div class="space-y-4 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p
                                            class="text-xs font-bold uppercase tracking-wider {{ $esBachillerato ? 'text-emerald-600' : 'text-sky-600' }}">
                                            {{ $esBachillerato ? 'Bachillerato' : 'Educación básica' }}</p>
                                        <h3 class="mt-1 font-bold text-slate-900 dark:text-white">{{ $nombre }}
                                        </h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $mes }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button"
                                            @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $periodo->id }} })"
                                            class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"><flux:icon.pencil-square
                                                class="h-4 w-4" /></button>
                                        <button type="button" @click="eliminar({{ $periodo->id }})"
                                            class="rounded-lg bg-red-100 p-2 text-red-700 dark:bg-red-950/50 dark:text-red-300">
                                            <flux:icon.trash class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <dl class="grid grid-cols-2 gap-3 text-xs">
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <dt class="text-slate-400">Nivel</dt>
                                        <dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">
                                            {{ $periodo->nivel->nombre ?? '—' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <dt class="text-slate-400">Ciclo</dt>
                                        <dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">
                                            {{ $periodo->cicloEscolar->inicio_anio ?? '—' }} -
                                            {{ $periodo->cicloEscolar->fin_anio ?? '—' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <dt class="text-slate-400">Inicio</dt>
                                        <dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">
                                            {{ $periodo->fecha_inicio ? \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') : '—' }}
                                        </dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <dt class="text-slate-400">Fin</dt>
                                        <dd class="mt-1 font-bold text-slate-700 dark:text-slate-200">
                                            {{ $periodo->fecha_fin ? \Carbon\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') : '—' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-5">{{ $periodos->links() }}</div>
            @endif
        </div>
    </section>

    <livewire:periodo.editar-periodo />
</div>
