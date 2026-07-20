<div x-data="{
    editando: false,
    restaurandoRetorno: false,

    llave() {
        return 'matricula_generacion_' + @js($slug_nivel);
    },

    llaveRetorno() {
        return 'matricula_return_context_' + @js($slug_nivel);
    },

    guardarFiltros(alumnoId) {
        const parametros = new URL(window.location.href).searchParams;
        const pagina = Number(parametros.get('page') || 1);

        const filtros = {
            ciclo_escolar_id: this.$wire.get('ciclo_escolar_id'),
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

            if (!fila && contexto.alumno_id) {
                await this.$wire.localizarAlumnoEnMatricula(Number(contexto.alumno_id));
                await this.$nextTick();
                await this.esperarPintado();
                fila = this.buscarFila(contexto.alumno_id);
            }

            if (fila) {
                fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fila.animate([
                    { backgroundColor: 'rgba(16, 185, 129, 0)', boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)' },
                    { backgroundColor: 'rgba(16, 185, 129, .14)', boxShadow: '0 0 0 5px rgba(16, 185, 129, .24)' },
                    { backgroundColor: 'rgba(16, 185, 129, 0)', boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)' },
                ], { duration: 1800, easing: 'ease-out' });
            } else {
                window.scrollTo({ top: Number(contexto.scroll_y || 0), behavior: 'smooth' });
            }
        } catch (error) {
            console.error('No fue posible restaurar el contexto de matrícula.', error);
        } finally {
            this.restaurandoRetorno = false;
            this.limpiarRetorno();
        }
    },

    buscarFila(alumnoId) {
        return document.getElementById(`matricula-row-${Number(alumnoId)}`);
    },

    esperarPintado() {
        return new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(resolve));
        });
    },

    limpiarRetorno() {
        localStorage.removeItem('matricula_return_pending');
        localStorage.removeItem('matricula_highlight_id');
    },

    archivar(id, nombre) {
        Swal.fire({
            title: 'Archivar alumno',
            html: `El expediente de <b>${nombre}</b> dejará de aparecer en la vista normal, pero sus datos y generación se conservarán.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, archivar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#006492'
        }).then((result) => result.isConfirmed && this.$wire.archivar(id));
    },

    activarPreinscripcion(id, nombre) {
        Swal.fire({
            title: 'Activar inscripción',
            text: `El alumno ${nombre} cambiará de Preinscrito a Activo y se habilitará su acceso.`,
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Motivo de activación',
            inputValue: 'Documentación validada y confirmación de inscripción.',
            inputPlaceholder: 'Describe brevemente por qué se activa la inscripción...',
            inputAttributes: {
                maxlength: 500,
                rows: 3
            },
            showCancelButton: true,
            confirmButtonText: 'Sí, activar inscripción',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            focusConfirm: false,
            preConfirm: (motivo) => {
                const texto = String(motivo || '').trim();

                if (texto.length < 5) {
                    Swal.showValidationMessage('Escribe un motivo de al menos 5 caracteres.');
                    return false;
                }

                return texto;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.$wire.activarPreinscripcion(id, result.value);
            }
        });
    }
}" x-init="restaurarRetorno()" class="space-y-5">

    {{-- Loader al regresar de edición --}}
    <div x-cloak x-show="restaurandoRetorno" x-transition.opacity
        class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-2xl dark:bg-neutral-900">
            <div
                class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-emerald-100 border-t-emerald-600">
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Regresando al alumno</h3>
            <p class="mt-1 text-sm text-slate-500">Restaurando filtros, página y ubicación…</p>
        </div>
    </div>

    {{-- Loader al abrir edición --}}
    <div x-cloak x-show="editando" x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-2xl dark:bg-neutral-900">
            <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-sky-100 border-t-sky-600">
            </div>
            <h3 class="font-bold text-slate-900 dark:text-white">Abriendo expediente</h3>
            <p class="mt-1 text-sm text-slate-500">Preparando la información del alumno…</p>
        </div>
    </div>

    {{-- Loader general de Livewire --}}
    <div wire:loading.delay.longer
        wire:target="ciclo_escolar_id,generacion_id,grado_id,semestre_id,grupo_id,estatus,search,mostrar_archivados,limpiarFiltros,cambiarGeneracionSeleccionados,exportarExcel,archivar,restaurar,activarPreinscripcion"
        class="fixed inset-0 z-[95] flex items-center justify-center bg-slate-950/35 p-4 backdrop-blur-[2px]">
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-2xl dark:bg-neutral-900">
            <div class="h-7 w-7 animate-spin rounded-full border-4 border-sky-100 border-t-sky-600"></div>
            <div>
                <p class="text-sm font-black text-slate-900 dark:text-white">Actualizando matrícula</p>
                <p class="text-xs text-slate-500">Espera un momento…</p>
            </div>
        </div>
    </div>

    {{-- Navegación por nivel --}}
    <div class="overflow-x-auto pb-1">
        <div class="flex min-w-max justify-center gap-2">
            @foreach ($niveles as $item)
                @php $activoNivel = $slug_nivel === $item->slug; @endphp
                <a wire:navigate
                    href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'matricula']) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                        {{ $activoNivel
                            ? 'border-sky-600 bg-sky-600 text-white shadow-lg shadow-sky-600/20'
                            : 'border-slate-200 bg-white text-slate-700 hover:border-sky-300 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200' }}">
                    <flux:icon.users class="h-4 w-4" />
                    {{ $item->nombre }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Encabezado --}}
    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-sky-700 via-blue-700 to-indigo-700 p-5 text-white sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight">Matrícula por generación</h1>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold ring-1 ring-white/25">
                            {{ $nivel?->nombre }}
                        </span>
                    </div>
                    <p class="mt-1 max-w-3xl text-sm text-blue-100">
                        Cada alumno pertenece a una sola generación. Las bajas, traslados, inactivos y egresados
                        conservan su generación para consultas posteriores.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="exportarExcel" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25 disabled:opacity-60">
                        <flux:icon.table-cells class="h-4 w-4" /> Excel
                    </button>
                    <a target="_blank"
                        href="{{ route(
                            'misrutas.matricula.historial.pdf',
                            array_filter(
                                [
                                    'slug_nivel' => $slug_nivel,
                                    'ciclo_escolar_id' => $ciclo_escolar_id,
                                    'generacion_id' => $generacion_id,
                                    'grado_id' => $grado_id,
                                    'semestre_id' => $semestre_id,
                                    'grupo_id' => $grupo_id,
                                    'estatus' => $estatus,
                                    'search' => $search,
                                    'mostrar_archivados' => $mostrar_archivados ? 1 : 0,
                                ],
                                fn($value) => $value !== null && $value !== '',
                            ),
                        ) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-sky-800 transition hover:bg-blue-50">
                        <flux:icon.document-arrow-down class="h-4 w-4" /> PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-3 xl:grid-cols-6 dark:bg-neutral-700">
            @foreach ([['label' => 'Alumnos', 'value' => $resumen['total'], 'icon' => 'users'], ['label' => 'Hombres', 'value' => $resumen['hombres'], 'icon' => 'user'], ['label' => 'Mujeres', 'value' => $resumen['mujeres'], 'icon' => 'user'], ['label' => 'Activos', 'value' => $resumen['activos'], 'icon' => 'check'], ['label' => 'Bajas / traslados', 'value' => $resumen['bajas'], 'icon' => 'user-minus'], ['label' => 'Egresados', 'value' => $resumen['egresados'], 'icon' => 'academic-cap']] as $dato)
                <div class="bg-white p-4 dark:bg-neutral-900">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                            @if ($dato['icon'] === 'user-minus')
                                <flux:icon.user-minus class="h-5 w-5" />
                            @elseif ($dato['icon'] === 'users')
                                <flux:icon.users class="h-5 w-5" />
                            @elseif ($dato['icon'] === 'academic-cap')
                                <flux:icon.academic-cap class="h-5 w-5" />
                            @elseif ($dato['icon'] === 'check')
                                <flux:icon.check class="h-5 w-5" />
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
                <p class="text-sm text-slate-500">Filtra el padrón actual o histórico de cada generación.</p>
            </div>
            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                <flux:icon.arrow-path class="h-4 w-4" /> Limpiar filtros
            </button>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <flux:field>
                <flux:label>Ciclo escolar</flux:label>
                <flux:select wire:model.live="ciclo_escolar_id">
                    @foreach ($ciclosEscolares as $cicloEscolar)
                        <flux:select.option value="{{ $cicloEscolar->id }}">
                            {{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}{{ $cicloEscolar->es_actual ? ' · Actual' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Generación</flux:label>
                <flux:select wire:model.live="generacion_id">
                    <flux:select.option value="">Generaciones activas</flux:select.option>
                    @foreach ($generaciones as $item)
                        <flux:select.option value="{{ $item->id }}">
                            {{ $item->etiqueta }}{{ $item->status ? '' : ' · Inactiva' }}
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

            @if ($this->esBachillerato())
                <flux:field>
                    <flux:label>Semestre</flux:label>
                    <flux:select wire:model.live="semestre_id" :disabled="$semestres->isEmpty()">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($semestres as $item)
                            <flux:select.option value="{{ $item->id }}">Semestre {{ $item->numero }}
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
                        <flux:select.option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Estatus</flux:label>
                <flux:select wire:model.live="estatus">
                    <flux:select.option value="todos">Todos</flux:select.option>
                    @foreach (\App\Services\GestionAcademicaService::ESTATUS as $estado)
                        <flux:select.option value="{{ $estado }}">{{ $this->etiquetaEstatus($estado) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field class="{{ $this->esBachillerato() ? 'xl:col-span-2' : 'xl:col-span-3' }}">
                <flux:label>Buscar</flux:label>
                <flux:input wire:model.live.debounce.350ms="search" type="search" icon="magnifying-glass"
                    placeholder="Nombre, matrícula, folio o CURP" />
            </flux:field>
        </div>

        <div class="mt-4">
            <flux:checkbox wire:model.live="mostrar_archivados" label="Incluir expedientes archivados" />
        </div>
    </section>

    {{-- Cambio masivo de asignación --}}
    <section
        class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/20">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    <flux:icon.pencil-square class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="font-black text-amber-950 dark:text-amber-100">Cambiar asignación seleccionada</h2>
                    <p class="text-sm text-amber-800/80 dark:text-amber-200/70">
                        Reemplaza ciclo escolar, generación, grado, semestre y grupo; el cambio queda registrado en la
                        bitácora.
                    </p>
                </div>
            </div>
            <span
                class="rounded-full bg-amber-200 px-3 py-1 text-xs font-black text-amber-900 dark:bg-amber-900/60 dark:text-amber-100">
                {{ $this->selectedCount }} seleccionado(s)
            </span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <flux:select wire:model.live="destino_ciclo_escolar_id">
                <flux:select.option value="">Ciclo escolar destino</flux:select.option>
                @foreach ($ciclosEscolares as $cicloEscolar)
                    <flux:select.option value="{{ $cicloEscolar->id }}">
                        {{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}{{ $cicloEscolar->es_actual ? ' · Actual' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="destino_generacion_id">
                <flux:select.option value="">Nueva generación</flux:select.option>
                @foreach ($generacionesDestino->where('status', true) as $item)
                    <flux:select.option value="{{ $item->id }}">{{ $item->etiqueta }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="destino_grado_id">
                <flux:select.option value="">Nuevo grado</flux:select.option>
                @foreach ($grados as $item)
                    <flux:select.option value="{{ $item->id }}">{{ $item->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->esBachillerato())
                <flux:select wire:model.live="destino_semestre_id" :disabled="$semestresDestino->isEmpty()">
                    <flux:select.option value="">Nuevo semestre</flux:select.option>
                    @foreach ($semestresDestino as $item)
                        <flux:select.option value="{{ $item->id }}">Semestre {{ $item->numero }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="destino_grupo_id" :disabled="$gruposDestino->isEmpty()">
                <flux:select.option value="">Nuevo grupo</flux:select.option>
                @foreach ($gruposDestino as $item)
                    <flux:select.option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="motivo_cambio" type="text" placeholder="Motivo obligatorio del cambio"
                class="{{ $this->esBachillerato() ? 'xl:col-span-3' : 'xl:col-span-3' }}" />

            <button type="button" wire:click="cambiarGeneracionSeleccionados" wire:loading.attr="disabled"
                @disabled($this->selectedCount === 0)
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50">
                <flux:icon.check class="h-4 w-4" /> Aplicar cambio
            </button>
        </div>

        @error('destino_ciclo_escolar_id')
            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @error('selected')
            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @error('destino_generacion_id')
            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @error('destino_grupo_id')
            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
        @enderror
        @error('motivo_cambio')
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
                    {{ $nivel?->nombre }} ·
                    {{ $generacion_id ? $generaciones->firstWhere('id', $generacion_id)?->etiqueta ?? 'Generación' : 'Generaciones activas' }}
                </p>
            </div>
            <div wire:loading.delay
                wire:target="ciclo_escolar_id,generacion_id,grado_id,semestre_id,grupo_id,estatus,search,mostrar_archivados"
                class="text-sm font-semibold text-sky-600">
                Actualizando información…
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1360px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase tracking-wide text-white dark:bg-black">
                    <tr>
                        <th class="px-4 py-3 text-center">
                            <flux:checkbox wire:model.live="selectPage" />
                        </th>
                        <th class="px-4 py-3">Matrícula / CURP</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Ubicación actual</th>
                        <th class="px-4 py-3">Estatus</th>
                        <th class="px-4 py-3">Fechas / motivo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        @php
                            $nombreCompleto = trim(
                                "{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}",
                            );
                            $estado = $alumno->estatus ?? 'activo';
                            $estadoClass = match ($estado) {
                                'baja_temporal'
                                    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
                                'baja_definitiva',
                                'trasladado'
                                    => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                'suspendido',
                                'inactivo'
                                    => 'bg-slate-200 text-slate-700 dark:bg-neutral-700 dark:text-slate-200',
                                'reingreso'
                                    => 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-200',
                                'egresado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
                                'no_promovido'
                                    => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200',
                                'preinscrito'
                                    => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800/50',
                                default
                                    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
                            };
                        @endphp
                        <tr id="matricula-row-{{ $alumno->id }}" data-matricula-alumno="{{ $alumno->id }}"
                            wire:key="matricula-generacion-{{ $alumno->id }}"
                            class="scroll-mt-28 align-top transition hover:bg-sky-50/50 dark:hover:bg-sky-950/10 {{ $alumno->deleted_at ? 'opacity-65' : '' }}">
                            <td class="px-4 py-4 text-center">
                                <flux:checkbox wire:model.live="selected" value="{{ $alumno->id }}" />
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-black text-slate-900 dark:text-white">{{ $alumno->matricula ?: '—' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ $alumno->curp ?: 'Sin CURP' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $nombreCompleto }}</p>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                        {{ $alumno->genero === 'H' ? 'Hombre' : ($alumno->genero === 'M' ? 'Mujer' : 'Sin género') }}
                                    </span>
                                    @if ($alumno->deleted_at)
                                        <span
                                            class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:bg-neutral-700 dark:text-slate-200">Archivado</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $alumno->generacion?->etiqueta ?? 'Sin generación' }}</p>
                                @if ($alumno->generacion && !$alumno->generacion->status)
                                    <span
                                        class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">Generación
                                        inactiva</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-800 dark:text-slate-100">
                                    {{ $alumno->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($alumno->grupo) }}
                                </p>
                                @if ($this->esBachillerato())
                                    <p class="mt-1 text-xs text-slate-500">Semestre
                                        {{ $alumno->semestre?->numero ?? '—' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-black {{ $estadoClass }}">
                                    {{ $this->etiquetaEstatus($estado) }}
                                </span>
                                @if ($estado === 'preinscrito')
                                    <p
                                        class="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 dark:text-amber-300">
                                        <flux:icon.clock class="h-3.5 w-3.5" /> Acceso pendiente
                                    </p>
                                @elseif (in_array($estado, ['activo', 'reingreso', 'no_promovido'], true))
                                    <p class="mt-2 text-[11px] font-bold text-sky-600">Ubicación actual</p>
                                @endif
                            </td>
                            <td class="max-w-xs px-4 py-4">
                                <p class="text-xs text-slate-500">Ingreso al plantel:
                                    <b
                                        class="text-slate-700 dark:text-slate-200">{{ optional($alumno->fecha_inscripcion)->format('d/m/Y') ?: '—' }}</b>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">Fecha del estatus:
                                    <b
                                        class="text-slate-700 dark:text-slate-200">{{ optional($alumno->fecha_estatus)->format('d/m/Y') ?: '—' }}</b>
                                </p>
                                @if ($alumno->motivo_estatus)
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500"
                                        title="{{ $alumno->motivo_estatus }}">{{ $alumno->motivo_estatus }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($estado === 'preinscrito' && !$alumno->deleted_at)
                                        <button type="button"
                                            x-on:click="activarPreinscripcion({{ $alumno->id }}, @js($nombreCompleto))"
                                            wire:loading.attr="disabled" wire:target="activarPreinscripcion"
                                            title="Activar inscripción"
                                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 text-xs font-black text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60">
                                            <flux:icon.check-circle class="h-4 w-4" />
                                            <span>Activar inscripción</span>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="abrirBitacora({{ $alumno->id }})"
                                        title="Ver bitácora"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-700 transition hover:bg-violet-100 dark:bg-violet-950/30 dark:text-violet-300">
                                        <flux:icon.clock class="h-4 w-4" />
                                    </button>
                                    <button type="button"
                                        x-on:click="abrirEdicion({{ $alumno->id }}, @js(route('misrutas.matricula.editar', ['slug_nivel' => $slug_nivel, 'inscripcion' => $alumno->id])))"
                                        title="Editar alumno"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700 transition hover:bg-sky-100 dark:bg-sky-950/30 dark:text-sky-300">
                                        <flux:icon.pencil-square class="h-4 w-4" />
                                    </button>
                                    @if ($alumno->deleted_at)
                                        <button type="button" wire:click="restaurar({{ $alumno->id }})"
                                            title="Restaurar"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            <flux:icon.arrow-uturn-left class="h-4 w-4" />
                                        </button>
                                    @else
                                        <button type="button"
                                            x-on:click="archivar({{ $alumno->id }}, @js($nombreCompleto))"
                                            title="Archivar"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-neutral-800 dark:text-slate-300">
                                            <flux:icon.archive-box class="h-4 w-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div
                                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                    <flux:icon.magnifying-glass class="h-7 w-7" />
                                </div>
                                <h3 class="mt-4 font-black text-slate-800 dark:text-white">No hay alumnos con estos
                                    filtros</h3>
                                <p class="mt-1 text-sm text-slate-500">Cambia la generación, el estatus o la búsqueda.
                                </p>
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

    {{-- Modal de bitácora --}}
    @if ($modalBitacora && $bitacoraAlumno)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
            wire:key="modal-bitacora-{{ $bitacoraAlumno->id }}">
            <div
                class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                <div
                    class="flex items-start justify-between gap-4 bg-gradient-to-r from-violet-700 to-indigo-700 p-5 text-white">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.2em] text-violet-200">Bitácora académica</p>
                        <h2 class="mt-1 text-xl font-black">
                            {{ trim("{$bitacoraAlumno->apellido_paterno} {$bitacoraAlumno->apellido_materno} {$bitacoraAlumno->nombre}") }}
                        </h2>
                        <p class="mt-1 text-sm text-violet-100">Matrícula: {{ $bitacoraAlumno->matricula }} ·
                            Generación: {{ $bitacoraAlumno->generacion?->etiqueta ?? '—' }}</p>
                    </div>
                    <button type="button" wire:click="cerrarBitacora"
                        class="rounded-xl bg-white/15 p-2 transition hover:bg-white/25">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-[calc(92vh-110px)] overflow-y-auto p-5 sm:p-6">
                    <div class="space-y-3">
                        @forelse ($bitacoraAlumno->cambiosAcademicos as $cambio)
                            <div class="relative rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-900 dark:text-white">
                                            {{ str($cambio->tipo)->replace('_', ' ')->title() }}</p>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $cambio->motivo ?: 'Sin motivo registrado' }}</p>
                                    </div>
                                    <span
                                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                        {{ optional($cambio->realizado_at)->format('d/m/Y H:i') ?: optional($cambio->created_at)->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-slate-400">Realizado por:
                                    {{ $cambio->usuario?->name ?? 'Sistema' }}</p>
                            </div>
                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-neutral-700">
                                No hay cambios registrados para este alumno.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
