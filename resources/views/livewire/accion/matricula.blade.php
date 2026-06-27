<div x-data="{
    editando: false,
    restaurandoRetorno: false,

    llave() {
        return 'matricula_historial_' + @js($slug_nivel);
    },

    llaveRetorno() {
        return 'matricula_return_context_' + @js($slug_nivel);
    },

    guardarFiltros(alumnoId) {
        const parametros = new URL(window.location.href).searchParams;
        const pagina = Number(parametros.get('page') || 1);

        const filtros = {
            ciclo_escolar_id: this.$wire.get('ciclo_escolar_id'),
            ciclo_id: this.$wire.get('ciclo_id'),
            generacion_id: this.$wire.get('generacion_id'),
            grado_id: this.$wire.get('grado_id'),
            semestre_id: this.$wire.get('semestre_id'),
            grupo_id: this.$wire.get('grupo_id'),
            estatus: this.$wire.get('estatus'),
            search: this.$wire.get('search'),
            mostrar_archivados: this.$wire.get('mostrar_archivados'),
            page: pagina,
        };

        const contexto = {
            alumno_id: Number(alumnoId),
            slug_nivel: @js($slug_nivel),
            url: window.location.href,
            scroll_y: window.scrollY,
            filtros,
            expires_at: Date.now() + (2 * 60 * 60 * 1000),
        };

        localStorage.setItem(this.llave(), JSON.stringify(filtros));
        localStorage.setItem(this.llaveRetorno(), JSON.stringify(contexto));
        localStorage.setItem('matricula_return_url', window.location.href);
        localStorage.setItem('matricula_return_pending', '1');
        localStorage.setItem('matricula_highlight_id', String(alumnoId));
    },

    abrirEdicion(id, url) {
        this.guardarFiltros(id);
        this.editando = true;

        setTimeout(() => {
            window.location.href = url;
        }, 300);
    },

    async restaurarRetorno() {
        if (localStorage.getItem('matricula_return_pending') !== '1') {
            return;
        }

        const raw = localStorage.getItem(this.llaveRetorno());

        if (!raw) {
            localStorage.removeItem('matricula_return_pending');
            return;
        }

        let contexto;

        try {
            contexto = JSON.parse(raw);
        } catch (error) {
            this.limpiarRetorno();
            return;
        }

        if (
            contexto.slug_nivel !== @js($slug_nivel) ||
            !contexto.expires_at ||
            Date.now() > Number(contexto.expires_at)
        ) {
            this.limpiarRetorno();
            return;
        }

        this.restaurandoRetorno = true;

        try {
            await this.$wire.restaurarFiltrosMatricula(contexto.filtros || {});
            await this.$nextTick();
            await this.esperarPintado();

            let fila = this.buscarFila(contexto.alumno_id);

            /*
             * Si el alumno cambió de grado, generación, semestre o grupo,
             * los filtros anteriores podrían ocultarlo. En ese caso se
             * localiza usando su trayectoria actual.
             */
            if (!fila && contexto.alumno_id) {
                await this.$wire.localizarAlumnoEnMatricula(
                    Number(contexto.alumno_id)
                );

                await this.$nextTick();
                await this.esperarPintado();
                fila = this.buscarFila(contexto.alumno_id);
            }

            if (fila) {
                fila.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });

                fila.animate(
                    [{
                            backgroundColor: 'rgba(16, 185, 129, 0)',
                            boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)',
                        },
                        {
                            backgroundColor: 'rgba(16, 185, 129, .14)',
                            boxShadow: '0 0 0 5px rgba(16, 185, 129, .24)',
                        },
                        {
                            backgroundColor: 'rgba(16, 185, 129, 0)',
                            boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)',
                        },
                    ], {
                        duration: 1800,
                        easing: 'ease-out',
                    },
                );
            } else {
                window.scrollTo({
                    top: Number(contexto.scroll_y || 0),
                    behavior: 'smooth',
                });
            }
        } catch (error) {
            console.error(
                'No fue posible restaurar el contexto de matrícula.',
                error
            );
        } finally {
            this.restaurandoRetorno = false;
            this.limpiarRetorno();
        }
    },

    buscarFila(alumnoId) {
        return document.getElementById(
            `matricula-row-${Number(alumnoId)}`
        );
    },

    esperarPintado() {
        return new Promise((resolve) => {
            requestAnimationFrame(() => {
                requestAnimationFrame(resolve);
            });
        });
    },

    limpiarRetorno() {
        localStorage.removeItem('matricula_return_pending');
        localStorage.removeItem('matricula_highlight_id');
    },

    archivar(id, nombre) {
        Swal.fire({
            title: 'Archivar alumno',
            html: `El expediente de <b>${nombre}</b> dejará de aparecer en la vista normal, pero toda su trayectoria se conservará.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, archivar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#006492'
        }).then((result) => result.isConfirmed && this.$wire.archivar(id));
    }
}" x-init="restaurarRetorno()" class="space-y-5">
    <div x-cloak x-show="restaurandoRetorno" x-transition.opacity
        class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-2xl dark:bg-neutral-900">
            <div
                class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-emerald-100 border-t-emerald-600">
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">
                Regresando al alumno
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                Restaurando filtros, página y ubicación…
            </p>
        </div>
    </div>

    <div x-cloak x-show="editando" x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-2xl dark:bg-neutral-900">
            <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-sky-100 border-t-sky-600">
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Abriendo expediente</h3>
            <p class="mt-1 text-sm text-slate-500">Preparando la información del alumno…</p>
        </div>
    </div>

    {{-- Navegación por nivel --}}
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max justify-center gap-2">
            @foreach ($niveles as $item)
                @php
                    $activo = $slug_nivel === $item->slug;
                @endphp
                <a wire:navigate
                    href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'matricula']) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                        {{ $activo
                            ? 'border-sky-600 bg-sky-600 text-white shadow-lg shadow-sky-600/20'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-sky-300 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' }}">
                    <flux:icon.users class="h-4 w-4" />
                    {{ $item->nombre }}
                </a>
            @endforeach
        </div>
    </div>


    {{-- REINGRESO / REINCORPORACIÓN --}}
    <section id="reingreso-alumno" class="scroll-mt-24 rounded-3xl border border-violet-200 bg-violet-50/40 p-4 dark:border-violet-900/50 dark:bg-violet-950/10">
        <details class="group" @if(request()->integer('reingreso') > 0) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-sky-600 text-white shadow-lg shadow-violet-500/20">
                        <flux:icon.arrow-path class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.18em] text-violet-600 dark:text-violet-300">Retorno</p>
                        <h2 class="font-black text-slate-900 dark:text-white">Reingresar exalumno o reincorporar alumno</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Conserva el mismo ID y abre una nueva trayectoria.</p>
                    </div>
                </div>
                <flux:icon.chevron-down class="h-5 w-5 text-slate-500 transition group-open:rotate-180" />
            </summary>
            <div class="mt-5 border-t border-violet-200 pt-5 dark:border-violet-900/50">
                <livewire:accion.reingreso-alumno :slug_nivel="$slug_nivel" :key="'reingreso-alumno-' . $slug_nivel" />
            </div>
        </details>
    </section>

    {{-- Encabezado y estado del ciclo --}}
    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-sky-700 via-blue-700 to-indigo-700 p-5 text-white sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight">Historial de matrícula</h1>
                        @if ($cicloSeleccionado)
                            @if ($cicloSeleccionado->es_actual)
                                <span
                                    class="rounded-full bg-emerald-400/20 px-3 py-1 text-xs font-bold ring-1 ring-emerald-200/40">Ciclo
                                    actual</span>
                            @elseif ($cicloSeleccionado->cerrado_at)
                                <span
                                    class="rounded-full bg-amber-400/20 px-3 py-1 text-xs font-bold ring-1 ring-amber-200/40">Ciclo
                                    cerrado</span>
                            @else
                                <span
                                    class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold ring-1 ring-white/25">Ciclo
                                    histórico</span>
                            @endif
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-blue-100">
                        Consulta activos, bajas, traslados, reingresos y cambios de grupo sin sobrescribir ciclos
                        anteriores.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="exportarMatricula" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25 disabled:opacity-60">
                        <flux:icon.table-cells class="h-4 w-4" /> Excel
                    </button>
                    <button type="button" wire:click="exportarPdf" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-sky-800 transition hover:bg-blue-50 disabled:opacity-60">
                        <flux:icon.document-arrow-down class="h-4 w-4" /> PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 dark:bg-neutral-700">
            @foreach ([['label' => 'Alumnos', 'value' => $total, 'icon' => 'users'], ['label' => 'Hombres', 'value' => $hombres, 'icon' => 'user'], ['label' => 'Mujeres', 'value' => $mujeres, 'icon' => 'user'], ['label' => 'Bajas / traslados', 'value' => $bajas, 'icon' => 'user-minus']] as $dato)
                <div class="bg-white p-4 dark:bg-neutral-900">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                            @if ($dato['icon'] === 'user-minus')
                                <flux:icon.user-minus class="h-5 w-5" />
                            @elseif ($dato['icon'] === 'users')
                                <flux:icon.users class="h-5 w-5" />
                            @else
                                <flux:icon.user class="h-5 w-5" />
                            @endif
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $dato['label'] }}
                            </p>
                            <p class="text-xl font-black text-slate-900 dark:text-white">
                                {{ number_format($dato['value']) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Filtros --}}
    <section
        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Contexto académico</h2>
                <p class="text-sm text-slate-500">El ciclo y el corte determinan qué etapa histórica se consulta.</p>
            </div>
            <button type="button" wire:click="clearFilters"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                <flux:icon.arrow-path class="h-4 w-4" /> Limpiar filtros
            </button>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:field>
                <flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    @foreach ($cicloEscolares as $item)
                        <flux:select.option value="{{ $item->id }}">
                            {{ $item->inicio_anio }}-{{ $item->fin_anio }}
                            {{ $item->es_actual ? ' · Actual' : ($item->cerrado_at ? ' · Cerrado' : '') }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Corte</flux:label>
                <flux:select wire:model.live="ciclo_id">
                    @foreach ($ciclos as $item)
                        <flux:select.option value="{{ $item->id }}">{{ $item->ciclo }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Generación</flux:label>
                <flux:select wire:model.live="generacion_id">
                    <flux:select.option value="">Todas</flux:select.option>
                    @foreach ($generaciones as $item)
                        <flux:select.option value="{{ $item->id }}">
                            {{ $item->anio_ingreso }}-{{ $item->anio_egreso }}
                        </flux:select.option>
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

            @if ($esBachillerato)
                <flux:field>
                    <flux:label>Semestre</flux:label>
                    <flux:select wire:model.live="semestre_id" :disabled="$semestres->isEmpty()">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($semestres as $item)
                            <flux:select.option value="{{ $item->id }}">
                                Semestre {{ $item->numero }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Grupo</flux:label>
                <flux:select wire:model.live="grupo_id" :disabled="$grupos->isEmpty()">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($grupos as $item)
                        <flux:select.option value="{{ $item->id }}">
                            {{ $this->textoGrupo($item) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Estatus</flux:label>
                <flux:select wire:model.live="estatus">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    @foreach (\App\Models\TrayectoriaAcademica::ESTATUS as $estado)
                        <flux:select.option value="{{ $estado }}">
                            {{ $this->etiquetaEstatus($estado) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="{{ $esBachillerato ? '' : 'xl:col-span-2' }}">
                <flux:label>Buscar</flux:label>
                <flux:input wire:model.live.debounce.350ms="search" type="search" icon="magnifying-glass"
                    placeholder="Nombre, matrícula actual o anterior, folio o CURP" />
            </flux:field>
        </div>

        <div class="mt-4">
            <flux:checkbox wire:model.live="mostrar_archivados" label="Incluir expedientes archivados" />
        </div>
    </section>

    {{-- Corrección histórica masiva --}}
    <section
        class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        <flux:icon.pencil-square class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="font-black text-amber-950 dark:text-amber-100">Corregir historial seleccionado</h2>
                        <p class="text-sm text-amber-800/80 dark:text-amber-200/70">
                            Crea una nueva estancia en este ciclo y corte; no sobrescribe la etapa anterior.
                        </p>
                    </div>
                </div>
            </div>
            <span
                class="rounded-full bg-amber-200 px-3 py-1 text-xs font-black text-amber-900 dark:bg-amber-900/60 dark:text-amber-100">
                {{ $this->selectedCount }} seleccionado(s)
            </span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:select wire:model.live="nueva_generacion_id">
                <flux:select.option value="">Nueva generación</flux:select.option>
                @foreach ($generaciones as $item)
                    <flux:select.option value="{{ $item->id }}">
                        {{ $item->anio_ingreso }}-{{ $item->anio_egreso }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="nuevo_grado_id">
                <flux:select.option value="">Nuevo grado</flux:select.option>
                @foreach ($grados as $item)
                    <flux:select.option value="{{ $item->id }}">{{ $item->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($esBachillerato)
                <flux:select wire:model.live="nuevo_semestre_id" :disabled="$nuevosSemestres->isEmpty()">
                    <flux:select.option value="">Nuevo semestre</flux:select.option>
                    @foreach ($nuevosSemestres as $item)
                        <flux:select.option value="{{ $item->id }}">
                            Semestre {{ $item->numero }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="nuevo_grupo_id" :disabled="$nuevosGrupos->isEmpty()">
                <flux:select.option value="">Nuevo grupo</flux:select.option>
                @foreach ($nuevosGrupos as $item)
                    <flux:select.option value="{{ $item->id }}">
                        {{ $this->textoGrupo($item) }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="motivo_correccion" type="text"
                placeholder="Motivo de la corrección (opcional)" class="xl:col-span-3" />

            <button type="button" wire:click="aplicarCorreccion" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-amber-700 disabled:opacity-50">
                <flux:icon.check class="h-4 w-4" /> Aplicar corrección
            </button>
        </div>

        @error('selected')
            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @error('nuevo_grupo_id')
            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </section>

    {{-- Tabla --}}
    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div
            class="flex flex-col gap-2 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Alumnos del contexto seleccionado</h2>
                <p class="text-sm text-slate-500">
                    {{ $cicloSeleccionado?->nombre ?? 'Sin ciclo' }} · {{ $corteSeleccionado?->ciclo ?? 'Sin corte' }}
                    · {{ $nivel?->nombre }}
                </p>
            </div>
            <div wire:loading.delay class="text-sm font-semibold text-sky-600">
                Actualizando información…
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1350px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white dark:bg-black">
                    <tr>
                        <th class="px-4 py-3 text-center">
                            <flux:checkbox wire:model.live="selectPage" />
                        </th>
                        <th class="px-4 py-3">Matrícula / CURP</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Ubicación</th>
                        <th class="px-4 py-3">Ciclo y corte</th>
                        <th class="px-4 py-3">Estatus</th>
                        <th class="px-4 py-3">Fechas / motivo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @if ($rows->isEmpty())
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                    <flux:icon.magnifying-glass class="h-7 w-7" />
                                </div>
                                <h3 class="mt-4 font-black text-slate-800 dark:text-white">No hay alumnos en este
                                    contexto</h3>
                                <p class="mt-1 text-sm text-slate-500">Cambia el ciclo, el corte o los filtros de
                                    búsqueda.</p>
                            </td>
                        </tr>
                    @else
                        @foreach ($rows as $row)
                            @php
                                $trayectoria = $row->getRelation('trayectoriaContexto');
                                $nombreCompleto = trim(
                                    "{$row->apellido_paterno} {$row->apellido_materno} {$row->nombre}",
                                );
                                $estado = $row->estatus_historial ?? 'activo';
                                $estadoClass = match ($estado) {
                                    'baja_temporal'
                                        => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
                                    'baja_definitiva',
                                    'traslado'
                                        => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                    'reingreso'
                                        => 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-200',
                                    'egresado',
                                    'promovido'
                                        => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
                                    'no_promovido'
                                        => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200',
                                    default
                                        => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
                                };
                            @endphp
                            <tr id="matricula-row-{{ $row->id }}" data-matricula-alumno="{{ $row->id }}"
                                wire:key="matricula-historica-{{ $row->id }}-{{ $row->trayectoria_id }}"
                                class="scroll-mt-28 align-top transition hover:bg-sky-50/50 dark:hover:bg-sky-950/10 {{ $row->deleted_at ? 'opacity-65' : '' }}">
                                <td class="px-4 py-4 text-center">
                                    <flux:checkbox wire:model.live="selected" value="{{ $row->id }}" />
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $row->matricula_contexto ?: '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $row->curp ?: 'Sin CURP' }}</p>
                                    @if ($row->matricula_contexto !== $row->matricula)
                                        <span
                                            class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">Matrícula
                                            histórica</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $nombreCompleto }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ $row->genero === 'H' ? 'Hombre' : ($row->genero === 'M' ? 'Mujer' : 'Sin género') }}</span>
                                        @if ($row->datos_reconstruidos)
                                            <span
                                                class="rounded-full bg-fuchsia-100 px-2 py-0.5 text-[10px] font-bold text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-200">Reconstruido</span>
                                        @endif
                                        @if ($row->deleted_at)
                                            <span
                                                class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-neutral-700 dark:text-slate-200">Archivado</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $row->generacion ? $row->generacion->anio_ingreso . '-' . $row->generacion->anio_egreso : '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-100">
                                        {{ $row->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($row->grupo) }}
                                    </p>
                                    @if ($esBachillerato)
                                        <p class="mt-1 text-xs text-slate-500">Semestre
                                            {{ $row->semestre?->numero ?? '—' }}</p>
                                    @endif
                                    @if (($trayectoria?->numero_estancia ?? 1) > 1)
                                        <p class="mt-1 text-xs font-semibold text-violet-600">Estancia
                                            {{ $trayectoria->numero_estancia }} en este corte</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $trayectoria?->cicloEscolar?->nombre ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $trayectoria?->ciclo?->ciclo ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-black {{ $estadoClass }}">
                                        {{ $this->etiquetaEstatus($estado) }}
                                    </span>
                                    @if ($trayectoria?->es_actual)
                                        <p class="mt-2 text-[11px] font-bold text-sky-600">Ubicación actual</p>
                                    @endif
                                </td>
                                <td class="max-w-xs px-4 py-4">
                                    <p class="text-xs text-slate-500">Inscripción: <b
                                            class="text-slate-700 dark:text-slate-200">{{ optional($trayectoria?->fecha_inscripcion ?? $trayectoria?->fecha_inicio)->format('d/m/Y') ?: '—' }}</b>
                                    </p>
                                    @if ($trayectoria?->fecha_baja)
                                        <p class="mt-1 text-xs text-red-600">Baja:
                                            <b>{{ $trayectoria->fecha_baja->format('d/m/Y') }}</b>
                                        </p>
                                    @endif
                                    @if ($trayectoria?->motivo_baja)
                                        <p class="mt-1 line-clamp-2 text-xs text-slate-500"
                                            title="{{ $trayectoria->motivo_baja }}">{{ $trayectoria->motivo_baja }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="abrirHistorial({{ $row->id }})"
                                            title="Ver historial completo"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-700 transition hover:bg-violet-100 dark:bg-violet-950/30 dark:text-violet-300">
                                            <flux:icon.clock class="h-4 w-4" />
                                        </button>
                                        <button type="button"
                                            x-on:click="abrirEdicion({{ $row->id }}, @js(route('misrutas.matricula.editar', ['slug_nivel' => $slug_nivel, 'inscripcion' => $row->id])))"
                                            title="Editar alumno"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950/30 dark:text-sky-300">
                                            <flux:icon.pencil-square class="h-4 w-4" />
                                        </button>
                                        @if ($row->deleted_at)
                                            <button type="button" wire:click="restaurar({{ $row->id }})"
                                                title="Restaurar"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                <flux:icon.arrow-uturn-left class="h-4 w-4" />
                                            </button>
                                        @else
                                            <button type="button"
                                                x-on:click="archivar({{ $row->id }}, @js($nombreCompleto))"
                                                title="Archivar"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-neutral-800 dark:text-slate-300">
                                                <flux:icon.archive-box class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-neutral-700">
                {{ $rows->onEachSide(1)->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>

    {{-- Modal historial individual --}}
    @if ($modalHistorial && $historialAlumno)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
            wire:key="modal-historial-{{ $historialAlumno->id }}">
            <div
                class="max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                <div
                    class="flex items-start justify-between gap-4 bg-gradient-to-r from-violet-700 to-indigo-700 p-5 text-white">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.2em] text-violet-200">Historial académico
                            completo</p>
                        <h2 class="mt-1 text-xl font-black">
                            {{ trim("{$historialAlumno->apellido_paterno} {$historialAlumno->apellido_materno} {$historialAlumno->nombre}") }}
                        </h2>
                        <p class="mt-1 text-sm text-violet-100">Matrícula vigente: {{ $historialAlumno->matricula }} ·
                            CURP: {{ $historialAlumno->curp ?: '—' }}</p>
                    </div>
                    <button type="button" wire:click="cerrarHistorial"
                        class="rounded-xl bg-white/15 p-2 transition hover:bg-white/25">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-[calc(92vh-110px)] space-y-6 overflow-y-auto p-5 sm:p-6">
                    <section>
                        <h3 class="mb-3 flex items-center gap-2 font-black text-slate-900 dark:text-white">
                            <flux:icon.academic-cap class="h-5 w-5 text-violet-600" /> Trayectoria por ciclo y corte
                        </h3>
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-700">
                            <table class="min-w-[1050px] w-full text-left text-sm">
                                <thead
                                    class="bg-slate-100 text-xs uppercase text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3">Ciclo / corte</th>
                                        <th class="px-4 py-3">Nivel</th>
                                        <th class="px-4 py-3">Generación</th>
                                        <th class="px-4 py-3">Grado / grupo</th>
                                        <th class="px-4 py-3">Estancia</th>
                                        <th class="px-4 py-3">Estatus</th>
                                        <th class="px-4 py-3">Periodo</th>
                                        <th class="px-4 py-3">Origen</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @forelse ($historialAlumno->trayectoriasAcademicas as $item)
                                        <tr>
                                            <td class="px-4 py-3 font-semibold">
                                                {{ $item->cicloEscolar?->nombre ?? '—' }}<br><span
                                                    class="text-xs font-normal text-slate-500">{{ $item->ciclo?->ciclo ?? '—' }}</span>
                                            </td>
                                            <td class="px-4 py-3">{{ $item->nivel?->nombre ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                {{ $item->generacion ? $item->generacion->anio_ingreso . '-' . $item->generacion->anio_egreso : '—' }}
                                            </td>
                                            <td class="px-4 py-3">{{ $item->grado?->nombre ?? '—' }} ·
                                                {{ $this->textoGrupo($item->grupo) }} @if ($item->semestre)
                                                    <br><span class="text-xs text-slate-500">Semestre
                                                        {{ $item->semestre->numero }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">#{{ $item->numero_estancia }}</td>
                                            <td class="px-4 py-3"><span
                                                    class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold dark:bg-neutral-800">{{ $item->etiqueta_estatus }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-xs">
                                                {{ optional($item->fecha_inicio ?? $item->fecha_inscripcion)->format('d/m/Y') ?: '—' }}<br>a
                                                {{ optional($item->fecha_fin ?? $item->fecha_baja)->format('d/m/Y') ?: 'actual' }}
                                            </td>
                                            <td class="px-4 py-3 text-xs">
                                                {{ str($item->origen)->replace('_', ' ')->title() }} @if ($item->datos_reconstruidos)
                                                    <br><b class="text-fuchsia-600">Reconstruido</b>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Sin
                                                trayectorias registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <section>
                            <h3 class="mb-3 flex items-center gap-2 font-black text-slate-900 dark:text-white">
                                <flux:icon.identification class="h-5 w-5 text-sky-600" /> Matrículas por nivel
                            </h3>
                            <div class="space-y-2">
                                @forelse ($historialAlumno->matriculasAlumno as $matricula)
                                    <div
                                        class="flex items-center justify-between rounded-2xl border border-slate-200 p-3 dark:border-neutral-700">
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white">
                                                {{ $matricula->matricula }}</p>
                                            <p class="text-xs text-slate-500">{{ $matricula->nivel?->nombre ?? '—' }}
                                                · Desde {{ optional($matricula->fecha_asignacion)->format('d/m/Y') }}
                                            </p>
                                        </div>
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-bold {{ $matricula->vigente ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $matricula->vigente ? 'Vigente' : 'Anterior' }}</span>
                                    </div>
                                @empty
                                    <p
                                        class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
                                        Sin historial de matrículas.</p>
                                @endforelse
                            </div>
                        </section>

                        <section>
                            <h3 class="mb-3 flex items-center gap-2 font-black text-slate-900 dark:text-white">
                                <flux:icon.clock class="h-5 w-5 text-amber-600" /> Línea de tiempo
                            </h3>
                            <div class="max-h-80 space-y-3 overflow-y-auto pr-1">
                                @forelse ($historialAlumno->movimientos as $movimiento)
                                    <div class="relative border-l-2 border-violet-200 pl-4 dark:border-violet-900">
                                        <span
                                            class="absolute -left-[5px] top-1 h-2 w-2 rounded-full bg-violet-600"></span>
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="font-bold text-slate-800 dark:text-slate-100">
                                                {{ str($movimiento->tipo)->replace('_', ' ')->title() }}</p>
                                            <span
                                                class="text-xs text-slate-500">{{ optional($movimiento->fecha)->format('d/m/Y') }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $movimiento->motivo ?: 'Sin motivo registrado' }}</p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $movimiento->cicloEscolar?->nombre }} ·
                                            {{ $movimiento->ciclo?->ciclo }} @if ($movimiento->usuario)
                                                · {{ $movimiento->usuario->name }}
                                            @endif
                                        </p>
                                    </div>
                                @empty
                                    <p
                                        class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
                                        Sin movimientos registrados.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
