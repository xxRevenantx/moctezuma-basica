<div id="liberacion-sueldos-formulario" class="space-y-5"
    x-data="{
        claveEstado: @js('moctezuma:persona-nivel:liberacion-sueldos:v1:usuario:' . (auth()->id() ?: 'invitado')),
        temporizadorEstado: null,
        search: @entangle('search').live,
        nivelFiltro: @entangle('nivelFiltro').live,
        gradoFiltro: @entangle('gradoFiltro').live,
        grupoFiltro: @entangle('grupoFiltro').live,
        rolFiltro: @entangle('rolFiltro').live,
        historialSearch: @entangle('historialSearch').live,
        historialNivel: @entangle('historialNivel').live,
        historialCiclo: @entangle('historialCiclo').live,
        seleccionados: @entangle('seleccionados').live,
        firmantes: @entangle('firmantes').live,
        fechaDocumento: @entangle('fechaDocumento').live,
        quincenaInicio: @entangle('quincenaInicio').live,
        quincenaFin: @entangle('quincenaFin').live,
        anio: @entangle('anio').live,
        cicloEscolar: @entangle('cicloEscolar').live,
        fechaReanudacion: @entangle('fechaReanudacion').live,
        franjaAnchoMm: @entangle('franjaAnchoMm').live,
        franjaAltoMm: @entangle('franjaAltoMm').live,
        franjaInferiorMm: @entangle('franjaInferiorMm').live,
        async init() {
            let guardado = null;

            try {
                const contenido = localStorage.getItem(this.claveEstado);
                guardado = contenido ? JSON.parse(contenido) : null;
            } catch (error) {
                localStorage.removeItem(this.claveEstado);
                console.warn('No fue posible leer el estado de Liberación de sueldos.', error);
            }

            if (guardado && typeof guardado === 'object') {
                try {
                    await this.$wire.restaurarEstadoLocal(guardado);
                } catch (error) {
                    console.warn('No fue posible restaurar Liberación de sueldos.', error);
                }
            }

            [
                'search', 'nivelFiltro', 'gradoFiltro', 'grupoFiltro', 'rolFiltro',
                'historialSearch', 'historialNivel', 'historialCiclo', 'seleccionados',
                'firmantes', 'fechaDocumento', 'quincenaInicio', 'quincenaFin', 'anio',
                'cicloEscolar', 'fechaReanudacion', 'franjaAnchoMm', 'franjaAltoMm',
                'franjaInferiorMm',
            ].forEach((propiedad) => this.$watch(propiedad, () => this.programarGuardado()));

            this.guardarEstado();
        },
        programarGuardado() {
            clearTimeout(this.temporizadorEstado);
            this.temporizadorEstado = setTimeout(() => this.guardarEstado(), 250);
        },
        guardarEstado() {
            const estado = {
                search: this.search,
                nivelFiltro: this.nivelFiltro,
                gradoFiltro: this.gradoFiltro,
                grupoFiltro: this.grupoFiltro,
                rolFiltro: this.rolFiltro,
                historialSearch: this.historialSearch,
                historialNivel: this.historialNivel,
                historialCiclo: this.historialCiclo,
                seleccionados: this.copiar(this.seleccionados, []),
                firmantes: this.copiar(this.firmantes, {}),
                fechaDocumento: this.fechaDocumento,
                quincenaInicio: this.quincenaInicio,
                quincenaFin: this.quincenaFin,
                anio: this.anio,
                cicloEscolar: this.cicloEscolar,
                fechaReanudacion: this.fechaReanudacion,
                franjaAnchoMm: this.franjaAnchoMm,
                franjaAltoMm: this.franjaAltoMm,
                franjaInferiorMm: this.franjaInferiorMm,
            };

            try {
                localStorage.setItem(this.claveEstado, JSON.stringify(estado));
            } catch (error) {
                console.warn('No fue posible guardar Liberación de sueldos.', error);
            }
        },
        copiar(valor, predeterminado) {
            try {
                return JSON.parse(JSON.stringify(valor ?? predeterminado));
            } catch (error) {
                return predeterminado;
            }
        },
    }"
    x-on:abrir-url-liberacion.window="window.open($event.detail.url, '_blank')"
    x-on:desplazar-liberacion-formulario.window="document.getElementById('liberacion-sueldos-formulario')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-[#006492]"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-amber-700 dark:text-amber-300">Documento oficial</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Liberación de sueldos</h2>
                    <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                        Genera constancias individuales o masivas para el personal activo. La clave presupuestal permanece en una sola línea con “S/N” y la firma de dirección queda vacía para llenarse manualmente.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-neutral-700 dark:bg-neutral-950">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Selección actual</p>
                    <p class="mt-1 text-2xl font-black text-[#006492] dark:text-sky-300">{{ count($seleccionados) }}</p>
                    @if (count($seleccionados))
                        <button type="button" wire:click="limpiarSeleccion" class="mt-1 text-xs font-bold text-slate-500 underline">Limpiar selección</button>
                    @endif
                </div>
            </div>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300">
                    <p class="font-black">Revisa la información:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    <div class="grid gap-5 2xl:grid-cols-[1.45fr_.9fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <div class="md:col-span-2 xl:col-span-2">
                    <flux:input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        label="Buscar personal activo"
                        placeholder="Nombre o función..."
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
                <div>
                    <flux:select wire:model.live="nivelFiltro" label="Nivel">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select wire:model.live="gradoFiltro" label="Grado" :disabled="$nivelFiltro === ''">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select wire:model.live="grupoFiltro" label="Grupo" :disabled="$gradoFiltro === ''">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">{{ $grupo->asignacionGrupo?->nombre ?? 'Grupo' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select wire:model.live="rolFiltro" label="Función / rol">
                        <flux:select.option value="">Todas</flux:select.option>
                        @foreach ($roles as $rol)
                            <flux:select.option value="{{ $rol->id }}">{{ $rol->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex items-end md:col-span-2 xl:col-span-6 xl:justify-end">
                    <button type="button" wire:click="seleccionarVisibles"
                        class="w-full rounded-2xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white hover:bg-sky-800 md:w-auto">
                        Seleccionar todo lo visible
                    </button>
                </div>
            </div>

            <div class="mt-4 max-h-[520px] overflow-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-950">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3">Personal</th>
                            <th class="px-4 py-3">Nivel</th>
                            <th class="px-4 py-3">Funciones activas</th>
                            <th class="px-4 py-3">C.C.T.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($personal as $asignacion)
                            @php
                                $persona = $asignacion->persona;
                                $funciones = $asignacion->detalles->where('estado', 'activo')->map(fn($d) => $d->personaRole?->rolePersona?->nombre)->filter()->unique()->implode(', ');
                                $nombre = trim(($persona?->nombre ?? '').' '.($persona?->apellido_paterno ?? '').' '.($persona?->apellido_materno ?? ''));
                            @endphp
                            <tr wire:key="liberacion-persona-{{ $asignacion->id }}" class="hover:bg-slate-50/70 dark:hover:bg-neutral-800/40">
                                <td class="px-4 py-3">
                                    <flux:checkbox wire:model.live="seleccionados" value="{{ $asignacion->id }}" />
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $nombre ?: 'Sin nombre' }}</p>
                                    <p class="text-xs text-slate-500">{{ $persona?->titulo ?: 'Sin título' }}</p>
                                </td>
                                <td class="px-4 py-3 font-bold">{{ $asignacion->nivel?->nombre ?? '—' }}</td>
                                <td class="max-w-sm px-4 py-3 text-xs text-slate-600 dark:text-slate-300">{{ $funciones ?: 'Sin función' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $asignacion->nivel?->cct ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No se encontró personal activo con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-5">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-black text-slate-950 dark:text-white">Datos del documento</h3>
                        <p class="text-xs text-slate-500">La fecha es automática, pero puede modificarse.</p>
                    </div>
                    @if ($editandoId)
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-[10px] font-black uppercase text-amber-700">Editando #{{ $editandoId }}</span>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <flux:input type="date" wire:model="fechaDocumento" label="Fecha del documento" />
                    <flux:input type="number" min="2000" max="2100" wire:model="anio" label="Año de pago" />

                    <div class="sm:col-span-2">
                        <flux:input
                            type="text"
                            wire:model="cicloEscolar"
                            label="Ciclo escolar"
                            placeholder="2025-2026"
                        />
                    </div>

                    <flux:input type="number" min="1" max="24" wire:model="quincenaInicio" label="Quincena inicial" />
                    <flux:input type="number" min="1" max="24" wire:model="quincenaFin" label="Quincena final" />

                    <div class="sm:col-span-2">
                        <flux:input type="date" wire:model="fechaReanudacion" label="Reanudación de labores" />
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                    <b>Campos manuales en el documento:</b> lugar de expedición, firma de dirección y una sola línea de clave presupuestal con “S/N”.
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white p-5 dark:border-neutral-800 dark:from-neutral-950 dark:to-neutral-900">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-[#006492]/10 text-[#006492] dark:bg-sky-400/10 dark:text-sky-300">
                            <flux:icon.photo class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">Elementos gráficos del formato</h3>
                            <p class="mt-1 text-xs text-slate-500">Configura por separado el logotipo y la franja inferior. Las imágenes se aplican al PDF, ZIP y Word.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-0 divide-y divide-slate-200 dark:divide-neutral-800">
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">Logotipo del encabezado</p>
                                <p class="text-xs text-slate-500">PNG, JPG, JPEG o WebP. Máximo 5 MB.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">Encabezado</span>
                        </div>

                        <div class="mt-4 flex min-h-24 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950">
                            @if ($logoNuevo)
                                <img src="{{ $logoNuevo->temporaryUrl() }}" class="max-h-20 max-w-full object-contain" alt="Vista previa del logotipo">
                            @elseif ($config?->logo_encabezado_path)
                                <img src="{{ Storage::disk('public')->url($config->logo_encabezado_path) }}" class="max-h-20 max-w-full object-contain" alt="Logo configurado">
                            @else
                                <img src="{{ asset('imagenes/liberacion-sueldos/logo-encabezado.png') }}" class="max-h-20 max-w-full object-contain" alt="Logo oficial">
                            @endif
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                            <flux:input type="file" wire:model="logoNuevo" label="Seleccionar logotipo" accept="image/png,image/jpeg,image/webp" />
                            <button type="button" wire:click="guardarLogo" wire:loading.attr="disabled" wire:target="guardarLogo,logoNuevo"
                                class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-4 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md disabled:opacity-50 dark:bg-white dark:text-slate-900">
                                Guardar logo
                            </button>
                            @if ($config?->logo_encabezado_path)
                                <button type="button" wire:click="restaurarLogo"
                                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                    Restaurar
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">Franja inferior</p>
                                <p class="text-xs text-slate-500">La imagen debe ser horizontal. Puedes ajustar sus medidas y posición en milímetros.</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Pie de página</span>
                        </div>

                        <div class="mt-4 flex min-h-20 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950">
                            @if ($franjaNueva)
                                <img src="{{ $franjaNueva->temporaryUrl() }}" class="max-h-16 w-full object-contain" alt="Vista previa de la franja">
                            @elseif ($config?->franja_inferior_path)
                                <img src="{{ Storage::disk('public')->url($config->franja_inferior_path) }}" class="max-h-16 w-full object-contain" alt="Franja configurada">
                            @else
                                <img src="{{ asset('images/franja-inferior.png') }}" class="max-h-16 w-full object-contain" alt="Franja predeterminada">
                            @endif
                        </div>

                        <div class="mt-3">
                            <flux:input type="file" wire:model="franjaNueva" label="Seleccionar franja horizontal" accept="image/png,image/jpeg,image/webp" />
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <flux:input type="number" step="0.1" min="50" max="210" wire:model="franjaAnchoMm" label="Ancho (mm)" />
                            <flux:input type="number" step="0.1" min="2" max="30" wire:model="franjaAltoMm" label="Alto (mm)" />
                            <flux:input type="number" step="0.1" min="0" max="30" wire:model="franjaInferiorMm" label="Separación inferior (mm)" />
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="button" wire:click="guardarFranja" wire:loading.attr="disabled" wire:target="guardarFranja,franjaNueva"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#006492] px-5 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-sky-800 hover:shadow-md disabled:opacity-50">
                                <flux:icon.check class="size-4" />
                                Guardar franja y medidas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @if ($nivelesSeleccionados->isNotEmpty())
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-200 bg-slate-50/70 p-5 dark:border-neutral-800 dark:bg-neutral-950/60">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-950 dark:text-white">Autoridades y firmantes por nivel</h3>
                        <p class="text-sm text-slate-500">El cuerpo siempre conserva los datos de dirección. Las firmas cambian automáticamente según la función del destinatario.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-[10px] font-black uppercase">
                        <span class="rounded-full bg-blue-50 px-3 py-1.5 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">Personal general: Dirección + Supervisión</span>
                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Directivos: Supervisión + Jefatura de sector</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 p-5 xl:grid-cols-2">
                @foreach ($nivelesSeleccionados as $nivel)
                    @php
                        $key = (string) $nivel->id;
                        $candidatos = $directoresPorNivel->get($nivel->id, collect());
                        $supervisores = $supervisoresPorNivel->get($nivel->id, collect());
                        $jefesSector = $jefesSectorPorNivel->get($nivel->id, collect());
                        $cantidadDirectivos = (int) $directivosSeleccionadosPorNivel->get($nivel->id, 0);
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-900" wire:key="firmante-nivel-{{ $nivel->id }}">
                        <div class="border-b border-slate-200 bg-gradient-to-r from-[#006492]/10 to-transparent p-4 dark:border-neutral-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-black text-[#006492] dark:text-sky-300">{{ $nivel->nombre }}</p>
                                    <p class="text-xs text-slate-500">C.C.T. {{ $nivel->cct ?: 'sin registrar' }}</p>
                                </div>
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if ($cantidadDirectivos > 0)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-800">{{ $cantidadDirectivos }} destinatario(s) directivo(s)</span>
                                    @endif
                                    @if ($supervisores->count() > 1)
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black text-rose-700">Supervisor obligatorio</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-4">
                            <div>
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-blue-50 text-xs font-black text-blue-700">1</span>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">Dirección del centro de trabajo</p>
                                        <p class="text-xs text-slate-500">Se usa en el cuerpo y firma los documentos del personal general.</p>
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <flux:select wire:model.live="firmantes.{{ $key }}.director_persona_id" label="Dirección desde la plantilla">
                                            <flux:select.option value="">Captura manual / autoridad del nivel</flux:select.option>
                                            @foreach ($candidatos as $candidato)
                                                <flux:select.option value="{{ $candidato->id }}">
                                                    {{ trim(($candidato->titulo ? $candidato->titulo.' ' : '').$candidato->nombre.' '.$candidato->apellido_paterno.' '.$candidato->apellido_materno) }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    <flux:input type="text" wire:model="firmantes.{{ $key }}.director_nombre" label="Nombre del director(a)" />
                                    <flux:input type="text" wire:model="firmantes.{{ $key }}.director_cargo" label="Cargo de dirección" class="uppercase" />
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-5 dark:border-neutral-800">
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-emerald-50 text-xs font-black text-emerald-700">2</span>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">Supervisión escolar</p>
                                        <p class="text-xs text-slate-500">Firma como Vo. Bo. del personal general y como firmante principal cuando el destinatario es directivo.</p>
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <flux:select wire:model.live="firmantes.{{ $key }}.supervisor_director_id" label="Supervisor(a) desde la base de datos">
                                            <flux:select.option value="">Seleccionar o capturar manualmente</flux:select.option>
                                            @foreach ($supervisores as $supervisor)
                                                <flux:select.option value="{{ $supervisor->id }}">
                                                    {{ trim(($supervisor->titulo ? $supervisor->titulo.' ' : '').$supervisor->nombre.' '.$supervisor->apellido_paterno.' '.$supervisor->apellido_materno) }} — {{ $supervisor->cargo }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    <flux:input type="text" wire:model="firmantes.{{ $key }}.supervisor_nombre" label="Nombre del supervisor(a)" />
                                    <flux:input type="text" wire:model="firmantes.{{ $key }}.supervisor_cargo" label="Cargo de supervisión" class="uppercase" />
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-5 dark:border-neutral-800">
                                <div class="mb-3 flex items-center gap-2">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-50 text-xs font-black text-amber-700">3</span>
                                    <div>
                                        <p class="text-sm font-black text-slate-900 dark:text-white">Jefatura de sector</p>
                                        <p class="text-xs text-slate-500">Solo se usa en la firma cuando el destinatario tiene una función de dirección o subdirección.</p>
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <flux:select wire:model.live="firmantes.{{ $key }}.jefe_sector_director_id" label="Jefe(a) de sector desde la base de datos">
                                            <flux:select.option value="">Captura manual</flux:select.option>
                                            @foreach ($jefesSector as $jefe)
                                                <flux:select.option value="{{ $jefe->id }}">
                                                    {{ trim(($jefe->titulo ? $jefe->titulo.' ' : '').$jefe->nombre.' '.$jefe->apellido_paterno.' '.$jefe->apellido_materno) }} — {{ $jefe->cargo }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    <flux:input type="text" wire:model="firmantes.{{ $key }}.jefe_sector_nombre" label="Nombre del jefe(a) de sector" />
                                    <flux:input type="text" wire:model="firmantes.{{ $key }}.jefe_sector_cargo" label="Cargo de jefatura" class="uppercase" />
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-950 dark:text-white">Vista previa y exportación</h3>
                <p class="text-sm text-slate-500">Cada persona ocupa una sola página. En selección múltiple, “PDF individual” descarga un ZIP con un PDF por trabajador.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="previsualizar" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-black text-violet-700 hover:bg-violet-100">Vista previa</button>
                <button type="button" wire:click="generar('pdf','individual')" class="rounded-xl bg-[#006492] px-4 py-2.5 text-sm font-black text-white hover:bg-sky-800">PDF individual</button>
                <button type="button" wire:click="generar('pdf','masivo')" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-black text-white hover:bg-blue-800">PDF masivo</button>
                <button type="button" wire:click="generar('zip','individual')" class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-black text-white hover:bg-amber-700">ZIP de PDF</button>
                <button type="button" wire:click="generar('word','masivo')" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white dark:bg-white dark:text-slate-900">Word</button>
                @if ($editandoId)
                    <button type="button" wire:click="guardarEdicion" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-700">Guardar cambios</button>
                @endif
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">Historial de liberaciones</h3>
                    <p class="text-sm text-slate-500">Revisa, descarga, edita, duplica o elimina documentos generados.</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-3">
                    <flux:input
                        type="search"
                        wire:model.live.debounce.350ms="historialSearch"
                        placeholder="Buscar en historial..."
                        icon="magnifying-glass"
                        aria-label="Buscar en historial"
                        clearable
                    />
                    <flux:select wire:model.live="historialNivel" aria-label="Filtrar historial por nivel">
                        <flux:select.option value="">Todos los niveles</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="historialCiclo" aria-label="Filtrar historial por ciclo escolar">
                        <flux:select.option value="">Todos los ciclos</flux:select.option>
                        @foreach ($ciclosHistorial as $ciclo)
                            <flux:select.option value="{{ $ciclo }}">{{ $ciclo }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-950">
                    <tr>
                        <th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Personal</th><th class="px-4 py-3">Nivel</th>
                        <th class="px-4 py-3">Quincenas</th><th class="px-4 py-3">Firmantes</th><th class="px-4 py-3">Generó</th><th class="px-4 py-3">Archivos</th><th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($historial as $item)
                        <tr wire:key="historial-liberacion-{{ $item->id }}" class="hover:bg-slate-50/70 dark:hover:bg-neutral-800/40">
                            <td class="px-4 py-3 whitespace-nowrap"><b>{{ $item->fecha_documento->format('d/m/Y') }}</b><br><span class="text-xs text-slate-500">{{ $item->created_at?->format('d/m/Y H:i') }}</span></td>
                            <td class="px-4 py-3 font-black">{{ $item->trabajador_nombre }}</td>
                            <td class="px-4 py-3">{{ $item->nivel_nombre }}<br><span class="font-mono text-xs text-slate-500">{{ $item->cct }}</span></td>
                            <td class="px-4 py-3">{{ $item->quincena_inicio }} y {{ $item->quincena_fin }} / {{ $item->anio }}<br><span class="text-xs text-slate-500">Ciclo {{ $item->ciclo_escolar ?: '—' }}</span></td>
                            <td class="px-4 py-3 text-xs">
                                @if ($item->tipo_firmantes === 'supervision_sector')
                                    <span class="mb-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-black uppercase text-amber-700">Supervisión + sector</span><br>
                                    {{ $item->supervisor_nombre ?: 'Sin supervisor' }}<br>
                                    <b>{{ $item->jefe_sector_nombre ?: 'Sin jefe de sector' }}</b>
                                @else
                                    <span class="mb-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase text-blue-700">Dirección + supervisión</span><br>
                                    {{ $item->director_nombre ?: 'Sin dirección' }}<br>
                                    <b>{{ $item->supervisor_nombre ?: 'Sin supervisor' }}</b>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $item->creador?->name ?? 'Sistema' }}</td>
                            <td class="px-4 py-3 text-xs"><span class="rounded bg-blue-50 px-2 py-1 font-black text-blue-700">{{ $item->archivo_pdf_path ? 'PDF' : 'PDF al descargar' }}</span> <span class="rounded bg-slate-100 px-2 py-1 font-black text-slate-700">{{ $item->archivo_word_path ? 'WORD' : 'WORD al descargar' }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a target="_blank" href="{{ route('misrutas.liberacion-sueldos.descargar', ['formato'=>'pdf','ids'=>$item->id]) }}" class="rounded-lg bg-blue-50 px-2.5 py-2 text-[10px] font-black text-blue-700">PDF</a>
                                    <a target="_blank" href="{{ route('misrutas.liberacion-sueldos.descargar', ['formato'=>'word','ids'=>$item->id]) }}" class="rounded-lg bg-slate-100 px-2.5 py-2 text-[10px] font-black text-slate-700">Word</a>
                                    <button type="button" wire:click="editarHistorial({{ $item->id }})" class="rounded-lg bg-amber-50 px-2.5 py-2 text-[10px] font-black text-amber-700">Editar</button>
                                    <button type="button" wire:click="duplicarHistorial({{ $item->id }})" class="rounded-lg bg-violet-50 px-2.5 py-2 text-[10px] font-black text-violet-700">Duplicar</button>
                                    <button type="button" wire:click="eliminarHistorial({{ $item->id }})" wire:confirm="¿Eliminar esta liberación del historial?" class="rounded-lg bg-rose-50 px-2.5 py-2 text-[10px] font-black text-rose-700">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-14 text-center text-slate-500">Todavía no hay liberaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
