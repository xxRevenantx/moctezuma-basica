@php
    $service = app(\App\Services\AlumnosNoVigentesService::class);
    $categorias = [
        'todos' => ['Todos', 'users', 'slate'],
        'preinscritos' => ['Preinscritos', 'clipboard-document-check', 'indigo'],
        'pendientes_reinscripcion' => ['Pendientes de reinscripción', 'clock', 'amber'],
        'no_reinscritos' => ['No reinscritos / no iniciaron', 'user-minus', 'rose'],
        'egresados' => ['Egresados', 'academic-cap', 'violet'],
        'regularizacion' => ['Pendientes de regularización', 'arrow-path', 'cyan'],
        'archivados' => ['Archivados', 'archive-box', 'slate'],
    ];
    $tonos = [
        'slate' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300',
        'cyan' => 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300',
    ];
@endphp

<div x-data="{
    activar(id, nombre) {
        Swal.fire({
            title: 'Activar preinscripción',
            text: `El alumno ${nombre} pasará a Matrícula activa.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#059669'
        }).then((result) => result.isConfirmed && this.$wire.activarPreinscripcion(id));
    },
    restaurar(id, nombre) {
        Swal.fire({
            title: 'Restaurar expediente',
            text: `Se restaurará el expediente de ${nombre} y conservará su estatus previo. Si era Activo, volverá a Matrícula activa.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Restaurar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#475569'
        }).then((result) => result.isConfirmed && this.$wire.restaurar(id));
    }
}" class="space-y-5">

    <div wire:loading.delay.longer
        wire:target="ciclo_escolar_id,generacion_id,grado_id,semestre_id,grupo_id,categoria,search,perPage,activarPreinscripcion,activarPreinscritosSeleccionados,registrarSeguimientoSeleccionados,restaurar,exportarExcel"
        class="fixed inset-0 z-[95] flex items-center justify-center bg-slate-950/35 p-4 backdrop-blur-[2px]">
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-2xl dark:bg-neutral-900">
            <div class="h-7 w-7 animate-spin rounded-full border-4 border-violet-100 border-t-violet-600"></div>
            <div>
                <p class="text-sm font-black text-slate-900 dark:text-white">Actualizando alumnos no vigentes</p>
                <p class="text-xs text-slate-500">Espera un momento…</p>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max justify-center gap-2">
            @foreach ($niveles as $item)
                @php $activoNivel = $slug_nivel === $item->slug; @endphp
                <a wire:navigate
                    href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'alumnos-no-vigentes']) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                        {{ $activoNivel
                            ? 'border-violet-600 bg-violet-600 text-white shadow-lg shadow-violet-600/20'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-violet-300 hover:text-violet-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' }}">
                    <flux:icon.user-group class="h-4 w-4" />
                    {{ $item->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    <section class="overflow-hidden rounded-3xl border border-violet-200 bg-white shadow-sm dark:border-violet-900/40 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-violet-700 via-indigo-700 to-sky-700 p-5 text-white sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight">Alumnos no vigentes</h1>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold ring-1 ring-white/25">
                            {{ $nivel?->nombre }}
                        </span>
                    </div>
                    <p class="mt-1 max-w-4xl text-sm text-violet-100">
                        Separa preinscritos, no reinscritos, alumnos que no iniciaron, egresados y casos pendientes de regularización. Las bajas y traslados permanecen exclusivamente en su módulo.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a wire:navigate
                        href="{{ route('submodulos.accion', ['slug_nivel' => $slug_nivel, 'accion' => 'matricula']) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                        <flux:icon.users class="h-4 w-4" /> Matrícula activa
                    </a>
                    <button type="button" wire:click="exportarExcel" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25 disabled:opacity-60">
                        <flux:icon.table-cells class="h-4 w-4" /> Excel
                    </button>
                    <a target="_blank"
                        href="{{ route('misrutas.alumnos-no-vigentes.pdf', array_filter([
                            'slug_nivel' => $slug_nivel,
                            'ciclo_escolar_id' => $ciclo_escolar_id,
                            'generacion_id' => $generacion_id,
                            'grado_id' => $grado_id,
                            'semestre_id' => $semestre_id,
                            'grupo_id' => $grupo_id,
                            'categoria' => $categoria,
                            'search' => $search,
                        ], fn ($value) => $value !== null && $value !== '')) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-violet-800 transition hover:bg-violet-50">
                        <flux:icon.document-arrow-down class="h-4 w-4" /> PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 xl:grid-cols-7 dark:bg-neutral-700">
            @foreach ($categorias as $clave => [$titulo, $icono, $tono])
                <button type="button" wire:click="seleccionarCategoria('{{ $clave }}')"
                    class="relative bg-white p-4 text-left transition hover:bg-violet-50/60 dark:bg-neutral-900 dark:hover:bg-violet-950/20">
                    @if ($categoria === $clave)
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-violet-500 to-sky-500"></span>
                    @endif
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $titulo }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($resumen[$clave] ?? 0) }}</p>
                </button>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Filtros del padrón no vigente</h2>
                <p class="text-sm text-slate-500">La clasificación usa el estado histórico del ciclo seleccionado.</p>
            </div>
            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                <flux:icon.arrow-path class="h-4 w-4" /> Limpiar filtros
            </button>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <flux:field>
                <flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    @foreach ($ciclosEscolares as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">
                            {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Generación</flux:label>
                <flux:select wire:model.live="generacion_id">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($generaciones as $item)
                        <flux:select.option value="{{ $item->id }}">{{ $item->etiqueta }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grado</flux:label>
                <flux:select wire:model.live="grado_id">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($grados as $item)
                        <flux:select.option value="{{ $item->id }}">{{ $item->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            @if ($this->esBachillerato())
                <flux:field>
                    <flux:label>Semestre</flux:label>
                    <flux:select wire:model.live="semestre_id" :disabled="$semestres->isEmpty()">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($semestres as $item)
                            <flux:select.option value="{{ $item->id }}">Semestre {{ $item->numero }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Grupo</flux:label>
                <flux:select wire:model.live="grupo_id" :disabled="$grupos->isEmpty()">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($grupos as $item)
                        <flux:select.option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="{{ $this->esBachillerato() ? 'xl:col-span-2' : 'xl:col-span-2' }}">
                <flux:label>Buscar</flux:label>
                <flux:input wire:model.live.debounce.350ms="search" icon="magnifying-glass"
                    placeholder="Nombre, matrícula, folio o CURP" />
            </flux:field>
        </div>
    </section>

    @if ($this->esCicloActual && $categoria === 'preinscritos')
        <section class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                <div class="min-w-0 flex-1">
                    <h2 class="font-black text-emerald-950 dark:text-emerald-100">Activación controlada de preinscritos</h2>
                    <p class="mt-1 text-sm text-emerald-800/80 dark:text-emerald-200/70">
                        Solo activa alumnos seleccionados que ya tengan ciclo, matrícula, generación, grado y grupo válidos.
                    </p>
                    <flux:input class="mt-3" wire:model="motivo_activacion" label="Motivo de activación" />
                    @error('motivo_activacion')<p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="button" wire:click="activarPreinscritosSeleccionados" wire:loading.attr="disabled"
                    @disabled($this->selectedCount === 0)
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <flux:icon.check-circle class="h-5 w-5" />
                    Activar {{ $this->selectedCount }} seleccionado(s)
                </button>
            </div>
        </section>
    @endif

    <section class="rounded-3xl border border-sky-200 bg-sky-50/70 p-5 shadow-sm dark:border-sky-900/40 dark:bg-sky-950/20">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="min-w-0 flex-1">
                <h2 class="font-black text-sky-950 dark:text-sky-100">Seguimiento administrativo</h2>
                <p class="mt-1 text-sm text-sky-800/80 dark:text-sky-200/70">
                    Registra una nota en la bitácora sin modificar el estatus ni reactivar al alumno.
                </p>
                <flux:input class="mt-3" wire:model="seguimiento_administrativo"
                    label="Nota para los seleccionados" placeholder="Ej. Se contactó al tutor y quedó pendiente confirmar reinscripción." />
                @error('seguimiento_administrativo')<p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                @error('selected')<p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="button" wire:click="registrarSeguimientoSeleccionados" wire:loading.attr="disabled"
                @disabled($this->selectedCount === 0)
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-700 px-5 py-3 text-sm font-black text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50">
                <flux:icon.document-check class="h-5 w-5" /> Registrar seguimiento
            </button>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-2 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">{{ $service->etiquetaCategoria($categoria) }}</h2>
                <p class="text-sm text-slate-500">{{ $alumnos->total() }} registro(s) · las bajas no se incluyen aquí.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                    {{ $this->selectedCount }} seleccionado(s)
                </span>
                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="20">20</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1380px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white dark:bg-black">
                    <tr>
                        <th class="px-4 py-3 text-center"><flux:checkbox wire:model.live="selectPage" /></th>
                        <th class="px-4 py-3">Matrícula / CURP</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Ubicación del ciclo</th>
                        <th class="px-4 py-3">Clasificación</th>
                        <th class="px-4 py-3">Fechas / motivo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        @php
                            $contexto = $service->contextoDe($alumno);
                            $estatus = $service->estatusDe($contexto, $alumno);
                            $clasificacion = $service->categoriaDe($contexto, $alumno);
                            $nombreCompleto = $this->nombreCompleto($alumno);
                        @endphp
                        <tr class="align-top transition hover:bg-violet-50/40 dark:hover:bg-violet-950/10" wire:key="no-vigente-{{ $alumno->id }}">
                            <td class="px-4 py-4 text-center"><flux:checkbox wire:model.live="selected" value="{{ $alumno->id }}" /></td>
                            <td class="px-4 py-4">
                                <p class="font-black text-slate-900 dark:text-white">{{ $service->matriculaDe($contexto, $alumno) ?: 'Sin matrícula' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $alumno->curp ?: 'Sin CURP' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-black text-slate-900 dark:text-white">{{ $nombreCompleto }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $alumno->genero === 'M' ? 'Mujer' : ($alumno->genero === 'H' ? 'Hombre' : 'Sin especificar') }}</p>
                            </td>
                            <td class="px-4 py-4 font-bold text-slate-700 dark:text-slate-200">{{ $contexto?->generacion?->etiqueta ?? '—' }}</td>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-800 dark:text-slate-100">
                                    {{ $contexto?->grado?->nombre ?? '—' }}
                                    @if ($contexto?->grupo?->asignacionGrupo?->nombre)
                                        · {{ $contexto->grupo->asignacionGrupo->nombre }}
                                    @endif
                                </p>
                                @if ($contexto?->semestre?->numero)
                                    <p class="mt-1 text-xs text-slate-500">Semestre {{ $contexto->semestre->numero }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $this->claseEstatus($estatus) }}">
                                    {{ $service->etiquetaEstatus($estatus) }}
                                </span>
                                <p class="mt-2 text-xs font-semibold text-slate-500">{{ $service->etiquetaCategoria($clasificacion) }}</p>
                            </td>
                            <td class="px-4 py-4 text-xs leading-5 text-slate-600 dark:text-slate-300">
                                <p>Ingreso: <b>{{ optional($service->fechaIngresoDe($contexto))->format('d/m/Y') ?: '—' }}</b></p>
                                <p>Salida/cierre: <b>{{ optional($service->fechaSalidaDe($contexto))->format('d/m/Y') ?: '—' }}</b></p>
                                @if (filled($service->motivoDe($contexto, $alumno)))
                                    <p class="mt-1 max-w-sm text-slate-500">{{ $service->motivoDe($contexto, $alumno) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($this->esCicloActual && $estatus === 'preinscrito' && !$alumno->trashed())
                                        <button type="button" x-on:click="activar({{ $alumno->id }}, @js($nombreCompleto))"
                                            title="Activar inscripción"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            <flux:icon.check-circle class="h-4 w-4" />
                                        </button>
                                    @endif

                                    @if (in_array($estatus, ['no_reinscrito', 'no_iniciado', 'egresado', 'reingreso'], true) && !$alumno->trashed())
                                        <a href="{{ route('misrutas.reingreso-alumno', ['slug_nivel' => $slug_nivel, 'reingreso' => $alumno->id]) }}"
                                            title="Iniciar reingreso o reincorporación"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 transition hover:bg-cyan-100 dark:bg-cyan-950/30 dark:text-cyan-300">
                                            <flux:icon.arrow-right-start-on-rectangle class="h-4 w-4" />
                                        </a>
                                    @endif

                                    <a href="{{ route('misrutas.expedientes.show', ['inscripcion' => $alumno->id]) }}"
                                        title="Ver expediente"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700 transition hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-300">
                                        <flux:icon.folder-open class="h-4 w-4" />
                                    </a>

                                    <a href="{{ route('misrutas.matricula.editar', ['slug_nivel' => $slug_nivel, 'inscripcion' => $alumno->id]) }}"
                                        title="Editar datos personales"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950/30 dark:text-sky-300">
                                        <flux:icon.pencil-square class="h-4 w-4" />
                                    </a>

                                    @if ($alumno->trashed())
                                        <button type="button" x-on:click="restaurar({{ $alumno->id }}, @js($nombreCompleto))"
                                            title="Restaurar expediente"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-slate-200 dark:bg-neutral-800 dark:text-slate-300">
                                            <flux:icon.arrow-uturn-left class="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-violet-50 text-violet-500 dark:bg-violet-950/30">
                                    <flux:icon.user-group class="h-7 w-7" />
                                </div>
                                <h3 class="mt-4 font-black text-slate-800 dark:text-white">No hay alumnos en esta clasificación</h3>
                                <p class="mt-1 text-sm text-slate-500">Cambia el ciclo, la categoría o la búsqueda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alumnos->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">
                {{ $alumnos->onEachSide(1)->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>
</div>
