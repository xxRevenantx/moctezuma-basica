<div>
    @if ($abierto && $tutor && $resumen)
        @php
            $puedeEditar = auth()->user()?->is_admin || auth()->user()?->canAccess('documentos.organizar') || auth()->user()?->canAccess('alumnos.editar');
            $documentosActuales = $documentos->where('es_actual', true);
        @endphp

        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 p-2 backdrop-blur-sm sm:p-4"
            x-data="{ tab: 'documentos', previewUrl: null, previewTitle: '' }" x-cloak>
            <section class="flex h-[96vh] w-full max-w-[1540px] flex-col overflow-hidden rounded-[28px] bg-slate-50 shadow-2xl dark:bg-neutral-950">
                <header class="relative overflow-hidden bg-gradient-to-r from-[#006492] via-sky-700 to-[#88AC2E] px-5 py-5 text-white sm:px-7">
                    <div class="pointer-events-none absolute -right-16 -top-24 size-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-black uppercase tracking-wider">Expediente documental del responsable</span>
                                <span class="rounded-full px-3 py-1 text-[11px] font-black uppercase {{ $tutor->activo ? 'bg-emerald-400/90 text-emerald-950' : 'bg-amber-300 text-amber-950' }}">
                                    {{ $tutor->activo ? 'Activo' : 'Archivado' }}
                                </span>
                            </div>
                            <h2 class="mt-2 truncate text-2xl font-black sm:text-3xl">{{ $tutor->nombre_completo }}</h2>
                            <p class="mt-1 text-sm text-white/80">
                                {{ $tutor->identidad_protegida }} · {{ $tutor->telefono_celular ?: ($tutor->telefono_casa ?: 'Sin teléfono') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($puedeEditar)
                                <button type="button" wire:click="abrirOrganizador"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-violet-500 px-4 py-2.5 text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-violet-400">
                                    <flux:icon name="squares-plus" class="size-4" /> Organizar páginas
                                </button>
                            @endif
                            <a href="{{ route('misrutas.expedientes-tutores.zip', $tutor) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-sky-800 shadow-lg transition hover:-translate-y-0.5">
                                <flux:icon name="archive-box" class="size-4" /> Descargar ZIP
                            </a>
                            <button type="button" wire:click="cerrar"
                                class="inline-flex size-11 items-center justify-center rounded-2xl border border-white/20 bg-white/10 transition hover:bg-white/20"
                                title="Cerrar expediente">
                                <flux:icon name="x-mark" class="size-5" />
                            </button>
                        </div>
                    </div>
                </header>

                <div class="grid border-b border-slate-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-[1fr_auto] sm:items-center sm:px-7">
                    <div class="flex flex-wrap gap-2">
                        @foreach (['documentos' => 'Documentos esperados', 'fuentes' => 'Archivos fuente', 'historial' => 'Historial y auditoría', 'configuracion' => 'Configuración'] as $clave => $etiqueta)
                            @if ($clave !== 'configuracion' || auth()->user()?->is_admin)
                                <button type="button" @click="tab = '{{ $clave }}'"
                                    :class="tab === '{{ $clave }}' ? 'bg-sky-700 text-white shadow' : 'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300'"
                                    class="rounded-xl px-3.5 py-2 text-xs font-black transition">
                                    {{ $etiqueta }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center gap-3 sm:mt-0">
                        <div class="min-w-[180px]">
                            <div class="mb-1 flex justify-between text-[11px] font-black text-slate-500">
                                <span>Integridad documental</span>
                                <span>{{ $resumen['completados'] }}/{{ $resumen['total'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-700">
                                <div class="h-full rounded-full bg-gradient-to-r from-sky-600 to-[#88AC2E]" style="width: {{ $resumen['porcentaje'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <main class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-7">
                    @if (($resumen['archivos_faltantes'] ?? 0) > 0)
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
                            Hay {{ $resumen['archivos_faltantes'] }} registro(s) cuyo archivo físico ya no está disponible. Sube una copia nueva para reparar el expediente.
                        </div>
                    @endif

                    @if ($pendientes->isNotEmpty())
                        <section class="mb-6 rounded-3xl border border-amber-200 bg-amber-50/80 p-5 dark:border-amber-900/50 dark:bg-amber-950/20">
                            <div class="flex items-start gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white">
                                    <flux:icon name="link" class="size-5" />
                                </div>
                                <div>
                                    <h3 class="font-black text-amber-950 dark:text-amber-100">Documentos antiguos pendientes de vincular</h3>
                                    <p class="text-sm text-amber-800 dark:text-amber-200">Se conservaron en el expediente del alumno. Revísalos antes de vincularlos al responsable.</p>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                @foreach ($pendientes as $pendiente)
                                    <article class="rounded-2xl border border-amber-200 bg-white p-4 dark:border-amber-900/50 dark:bg-neutral-900">
                                        <p class="font-black text-slate-900 dark:text-white">{{ $pendiente->documentoAlumno?->tipoDocumento?->nombre ?? 'Documento antiguo' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Alumno: {{ trim(($pendiente->inscripcion?->apellido_paterno ?? '') . ' ' . ($pendiente->inscripcion?->apellido_materno ?? '') . ' ' . ($pendiente->inscripcion?->nombre ?? '')) }}
                                            · {{ $pendiente->inscripcion?->matricula }}
                                        </p>
                                        <p class="mt-2 text-xs text-amber-700">{{ $pendiente->motivo }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($pendiente->documentoAlumno?->archivo_existe)
                                                <a href="{{ route('misrutas.expedientes.preview', $pendiente->documentoAlumno) }}" target="_blank"
                                                    class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700">Ver archivo</a>
                                            @endif
                                            @if ($puedeEditar)
                                                <button type="button" wire:click="vincularLegado({{ $pendiente->id }}, {{ $tutor->id }})"
                                                    wire:confirm="¿Vincular este documento antiguo al expediente de {{ $tutor->nombre_completo }}?"
                                                    class="rounded-xl bg-amber-600 px-3 py-2 text-xs font-black text-white hover:bg-amber-700">
                                                    Vincular a este responsable
                                                </button>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <div x-show="tab === 'documentos'" x-cloak>
                        <section class="grid gap-4 xl:grid-cols-2">
                            @foreach ($resumen['items'] as $item)
                                @php
                                    $actual = $documentosActuales->where('tipo_documento_tutor_id', $item['tipo_id'])->sortByDesc('version')->first();
                                    $versiones = $documentos->where('tipo_documento_tutor_id', $item['tipo_id'])->sortByDesc('version');
                                @endphp
                                <article x-data="{ historial: false }" wire:key="tipo-tutor-{{ $item['tipo_id'] }}"
                                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                    <div class="h-1.5 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>
                                    <div class="p-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                                    <flux:icon name="document" class="size-5" />
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h3 class="font-black text-slate-900 dark:text-white">{{ $item['nombre'] }}</h3>
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500 dark:bg-neutral-800">
                                                            {{ $item['obligatorio'] ? 'Obligatorio' : 'Opcional' }}
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $item['descripcion'] }}</p>
                                                </div>
                                            </div>
                                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $item['estado'] === 'validado' ? 'bg-emerald-50 text-emerald-700' : ($item['estado'] === 'no_aplica' ? 'bg-violet-50 text-violet-700' : ($item['presente'] ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700')) }}">
                                                {{ str_replace('_', ' ', $item['estado']) }}
                                            </span>
                                        </div>

                                        @if ($item['archivo_faltante'])
                                            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700">
                                                La base de datos conserva el registro, pero el archivo físico no está disponible.
                                            </div>
                                        @endif

                                        @if ($item['no_aplica'])
                                            <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-3 text-xs text-violet-800">
                                                <strong>No aplica:</strong> {{ $item['motivo_no_aplica'] }}
                                            </div>
                                        @endif

                                        @if ($actual)
                                            <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                                                <p class="truncate text-sm font-black text-slate-800 dark:text-slate-100" title="{{ $actual->nombre_original }}">{{ $actual->nombre_original }}</p>
                                                <p class="mt-1 text-xs text-slate-500">v{{ $actual->version }} · {{ $actual->paginas_total }} página(s) · {{ $actual->tamano_legible }} · {{ $actual->created_at?->format('d/m/Y H:i') }}</p>
                                                <p class="mt-1 text-xs text-slate-500">Subió: {{ $actual->usuarioQueSubio?->name ?? 'Sistema' }}</p>
                                            </div>
                                        @endif

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @if ($actual?->archivo_existe)
                                                <button type="button"
                                                    @click="previewUrl = @js(route('misrutas.expedientes-tutores.preview', $actual)); previewTitle = @js($item['nombre'] . ' · versión ' . $actual->version)"
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-black text-sky-700">
                                                    <flux:icon name="eye" class="size-3.5" /> Vista previa
                                                </button>
                                                <a href="{{ route('misrutas.expedientes-tutores.preview', $actual) }}" target="_blank"
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200 px-3 py-2 text-xs font-black text-sky-700">
                                                    <flux:icon name="arrow-top-right-on-square" class="size-3.5" /> Nueva pestaña
                                                </a>
                                                <a href="{{ route('misrutas.expedientes-tutores.download', $actual) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">
                                                    <flux:icon name="arrow-down-tray" class="size-3.5" /> Descargar
                                                </a>
                                            @endif
                                            @if ($puedeEditar)
                                                <button type="button" wire:click="abrirCarga({{ $item['tipo_id'] }})"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#006492] px-3 py-2 text-xs font-black text-white hover:bg-sky-800">
                                                    <flux:icon name="arrow-up-tray" class="size-3.5" /> {{ $actual ? 'Reemplazar' : 'Subir archivo' }}
                                                </button>
                                                <button type="button" wire:click="abrirNoAplica({{ $item['tipo_id'] }})"
                                                    class="rounded-xl border border-violet-200 px-3 py-2 text-xs font-black text-violet-700">No aplica</button>
                                                @if ($actual)
                                                    <select wire:change="actualizarEstado({{ $actual->id }}, $event.target.value)"
                                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                                        @foreach (['recibido', 'validado', 'rechazado'] as $estado)
                                                            <option value="{{ $estado }}" @selected($actual->estado === $estado)>{{ ucfirst($estado) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" wire:click="archivarDocumento({{ $actual->id }})"
                                                        wire:confirm="¿Archivar esta versión vigente? El archivo se conservará en el historial."
                                                        class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-700">Archivar</button>
                                                @endif
                                            @endif
                                        </div>

                                        @if ($versiones->isNotEmpty())
                                            <button type="button" @click="historial = !historial"
                                                class="mt-4 flex w-full items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 dark:border-neutral-700 dark:text-slate-300">
                                                <span>Historial de versiones ({{ $versiones->count() }})</span>
                                                <flux:icon name="chevron-down" class="size-4 transition" x-bind:class="historial ? 'rotate-180' : ''" />
                                            </button>
                                            <div x-show="historial" x-collapse class="mt-2 space-y-2">
                                                @foreach ($versiones as $version)
                                                    <div class="flex flex-col gap-2 rounded-xl bg-slate-50 p-3 text-xs dark:bg-neutral-950 sm:flex-row sm:items-center sm:justify-between">
                                                        <div>
                                                            <p class="font-black text-slate-800 dark:text-slate-100">v{{ $version->version }} · {{ ucfirst($version->estado) }} {{ $version->es_actual ? '· vigente' : '· histórica' }}</p>
                                                            <p class="text-slate-500">{{ $version->created_at?->format('d/m/Y H:i') }} · {{ $version->usuarioQueSubio?->name ?? 'Sistema' }}</p>
                                                        </div>
                                                        @if ($version->archivo_existe)
                                                            <a href="{{ route('misrutas.expedientes-tutores.preview', $version) }}" target="_blank" class="font-black text-sky-700">Abrir versión</a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </section>
                    </div>

                    <div x-show="tab === 'fuentes'" x-cloak>
                        <section class="rounded-3xl border border-violet-200 bg-violet-50/50 p-5 dark:border-violet-900/40 dark:bg-violet-950/10">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">Archivos originales y combinados</h3>
                                    <p class="text-sm text-slate-500">Se conservan por separado para reorganizar páginas sin perder el archivo recibido.</p>
                                </div>
                                @if ($puedeEditar && $fuentes->isNotEmpty())
                                    <button type="button" wire:click="abrirOrganizador" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white">Organizar fuentes</button>
                                @endif
                            </div>
                            <div class="mt-5 grid gap-3 lg:grid-cols-2">
                                @forelse ($fuentes as $fuente)
                                    <article class="rounded-2xl border border-violet-100 bg-white p-4 dark:border-violet-900/40 dark:bg-neutral-900">
                                        <p class="truncate font-black text-slate-900 dark:text-white">{{ $fuente->nombre_original }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $fuente->paginas }} página(s) · {{ $fuente->tamano_legible }} · {{ $fuente->created_at?->format('d/m/Y H:i') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Subió: {{ $fuente->usuario?->name ?? 'Sistema' }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a href="{{ route('misrutas.expedientes-tutores.fuentes.preview', $fuente) }}" target="_blank" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700">Vista previa</a>
                                            <a href="{{ route('misrutas.expedientes-tutores.fuentes.download', $fuente) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700">Original</a>
                                            @if ($puedeEditar && !$fuente->protegido)
                                                <button type="button" wire:click="abrirOrganizador({{ $fuente->id }})" class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white">Organizar</button>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="lg:col-span-2 rounded-2xl border border-dashed border-violet-200 bg-white p-8 text-center text-sm text-slate-500 dark:bg-neutral-900">Aún no hay archivos fuente.</div>
                                @endforelse
                            </div>
                        </section>
                    </div>

                    <div x-show="tab === 'historial'" x-cloak>
                        <div class="grid gap-5 xl:grid-cols-2">
                            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                                    <h3 class="font-black text-slate-900 dark:text-white">Todas las versiones documentales</h3>
                                </div>
                                <div class="max-h-[58vh] overflow-y-auto divide-y divide-slate-100 dark:divide-neutral-800">
                                    @forelse ($documentos as $documento)
                                        <div class="flex items-center justify-between gap-3 p-4">
                                            <div class="min-w-0">
                                                <p class="truncate font-black text-slate-900 dark:text-white">{{ $documento->tipoDocumento?->nombre }} · v{{ $documento->version }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ ucfirst($documento->estado) }} · {{ $documento->created_at?->format('d/m/Y H:i') }} · {{ $documento->usuarioQueSubio?->name ?? 'Sistema' }}</p>
                                            </div>
                                            @if ($documento->archivo_existe)
                                                <a href="{{ route('misrutas.expedientes-tutores.preview', $documento) }}" target="_blank" class="shrink-0 text-xs font-black text-sky-700">Ver</a>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="p-8 text-center text-sm text-slate-500">Sin versiones registradas.</p>
                                    @endforelse
                                </div>
                            </section>

                            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                                    <h3 class="font-black text-slate-900 dark:text-white">Bitácora de cambios</h3>
                                    <p class="text-sm text-slate-500">Quién subió, reemplazó, organizó, validó o archivó.</p>
                                </div>
                                <div class="max-h-[58vh] overflow-y-auto divide-y divide-slate-100 dark:divide-neutral-800">
                                    @forelse ($eventos as $evento)
                                        <div class="p-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-black text-slate-900 dark:text-white">{{ str($evento->accion)->headline() }}</p>
                                                <span class="text-[11px] font-bold text-slate-400">{{ $evento->created_at?->format('d/m/Y H:i') }}</span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $evento->descripcion }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $evento->usuario?->name ?? 'Sistema' }} · {{ $evento->ip ?: 'IP no disponible' }}</p>
                                        </div>
                                    @empty
                                        <p class="p-8 text-center text-sm text-slate-500">Sin eventos registrados.</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    </div>

                    @if (auth()->user()?->is_admin)
                        <div x-show="tab === 'configuracion'" x-cloak>
                            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                                    <h3 class="font-black text-slate-900 dark:text-white">Control de documentos esperados del responsable</h3>
                                    <p class="text-sm text-slate-500">Activa o desactiva tipos y define cuáles cuentan como obligatorios.</p>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-neutral-800">
                                    @foreach ($tipos as $tipo)
                                        <div class="space-y-3 p-4">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="font-black text-slate-900 dark:text-white">{{ $tipo['nombre'] }}</p>
                                                    <p class="text-xs text-slate-500">{{ $tipo['descripcion'] }}</p>
                                                </div>
                                                <div class="flex shrink-0 items-center gap-4 text-xs font-black text-slate-600 dark:text-slate-300">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="checkbox" @checked($tipo['activo']) wire:change="actualizarConfiguracionTipo({{ $tipo['id'] }}, 'activo', $event.target.checked)" class="rounded border-slate-300">
                                                        Activo
                                                    </label>
                                                    <label class="inline-flex items-center gap-2" title="Obligatorio para cualquier responsable, sin importar su parentesco">
                                                        <input type="checkbox" @checked($tipo['es_obligatorio']) wire:change="actualizarConfiguracionTipo({{ $tipo['id'] }}, 'es_obligatorio', $event.target.checked)" class="rounded border-slate-300">
                                                        Obligatorio global
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-neutral-800 dark:bg-neutral-950">
                                                <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-500">Obligatorio solo para estos parentescos</p>
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($parentescosConfigurables as $parentescoConfigurable)
                                                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-black text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300">
                                                            <input type="checkbox"
                                                                @checked(in_array($parentescoConfigurable, $tipo['obligatorio_parentescos'] ?? [], true))
                                                                wire:change="actualizarObligatorioParentesco({{ $tipo['id'] }}, @js($parentescoConfigurable), $event.target.checked)"
                                                                class="rounded border-slate-300">
                                                            {{ $parentescoConfigurable }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <p class="mt-2 text-[11px] text-slate-500">La regla se activa cuando el responsable tiene al menos una relación vigente con el parentesco seleccionado.</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    @endif
                </main>

                <div x-show="previewUrl" x-cloak
                    class="fixed inset-0 z-[125] flex items-center justify-center bg-slate-950/80 p-2 backdrop-blur-sm sm:p-5"
                    @keydown.escape.window="previewUrl = null; previewTitle = ''">
                    <div class="flex h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900"
                        @click.outside="previewUrl = null; previewTitle = ''">
                        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wider text-sky-700">Vista previa del documento</p>
                                <h3 class="truncate font-black text-slate-900 dark:text-white" x-text="previewTitle"></h3>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <a x-bind:href="previewUrl" target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">
                                    <flux:icon name="arrow-top-right-on-square" class="size-4" /> Abrir en pestaña
                                </a>
                                <button type="button" @click="previewUrl = null; previewTitle = ''"
                                    class="inline-flex size-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-200">
                                    <flux:icon name="x-mark" class="size-5" />
                                </button>
                            </div>
                        </div>
                        <div class="min-h-0 flex-1 bg-slate-100 p-2 dark:bg-neutral-950 sm:p-3">
                            <iframe x-bind:src="previewUrl" class="h-full w-full rounded-2xl bg-white" title="Vista previa del documento"></iframe>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($mostrarCarga && $abierto)
        @php $tipoCarga = collect($tipos)->firstWhere('id', $tipoDocumentoId); @endphp
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-neutral-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-neutral-800">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Subir {{ $tipoCarga['nombre'] ?? 'documento' }}</h3>
                        <p class="text-sm text-slate-500">El archivo se conservará como fuente antes de confirmar su organización.</p>
                    </div>
                    <button type="button" wire:click="cerrarCarga" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100"><flux:icon name="x-mark" class="size-5" /></button>
                </div>
                <div class="mx-5 mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/20 dark:text-sky-200">
                    <strong>Documento compartido:</strong> al confirmar el reemplazo, la nueva versión aparecerá automáticamente en
                    {{ $tutor?->relacionesActivas?->count() ?? 0 }} expediente(s) de alumno relacionados con este responsable. La versión anterior se conservará en el historial.
                </div>
                <form wire:submit="subirDocumento" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-sm font-black text-slate-700 dark:text-slate-200">Archivo</label>
                        <input type="file" wire:model="archivos" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple
                            class="block w-full rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm dark:border-neutral-700 dark:bg-neutral-950">
                        <p class="mt-1 text-xs text-slate-500">Selecciona hasta 10 archivos. PDF, JPG, JPEG, PNG o WEBP · máximo {{ config('expedientes_organizador.max_upload_mb', 30) }} MB por archivo.</p>
                        @if (count($archivos) > 0)
                            <p class="mt-2 rounded-xl bg-sky-50 px-3 py-2 text-xs font-black text-sky-700">{{ count($archivos) }} archivo(s) seleccionado(s).</p>
                        @endif
                        @error('archivos') <p class="mt-1 text-sm font-bold text-rose-600">{{ $message }}</p> @enderror
                        @error('archivos.*') <p class="mt-1 text-sm font-bold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-black text-slate-700 dark:text-slate-200">Contenido</span>
                            <select wire:model="contenidoArchivo" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                                <option value="un_documento">Un documento</option>
                                <option value="varios_documentos">Varios documentos combinados</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-black text-slate-700 dark:text-slate-200">Origen</span>
                            <select wire:model="origen" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                                <option value="subido">Subido</option>
                                <option value="digitalizado">Digitalizado</option>
                                <option value="externo">Externo</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-black text-slate-700 dark:text-slate-200">Fecha del documento</span>
                            <input type="date" wire:model="fechaDocumento" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-black text-slate-700 dark:text-slate-200">Folio</span>
                            <input type="text" wire:model="folio" maxlength="120" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950">
                        </label>
                    </div>
                    <label class="block">
                        <span class="mb-1 block text-sm font-black text-slate-700 dark:text-slate-200">Observaciones</span>
                        <textarea wire:model="observaciones" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 dark:border-neutral-700 dark:bg-neutral-950"></textarea>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="permitirDuplicado" class="rounded border-slate-300">
                        Permitir otra copia aunque el archivo ya exista
                    </label>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-neutral-800">
                        <button type="button" wire:click="cerrarCarga" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="archivos,subirDocumento" class="rounded-xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white disabled:opacity-60">
                            <span wire:loading.remove wire:target="subirDocumento">Guardar y organizar</span>
                            <span wire:loading wire:target="subirDocumento">Procesando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($mostrarNoAplica && $abierto)
        <div class="fixed inset-0 z-[115] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl dark:bg-neutral-900">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Marcar documento como “No aplica”</h3>
                <p class="mt-1 text-sm text-slate-500">La justificación quedará registrada en el historial.</p>
                <textarea wire:model="motivoNoAplica" rows="4" class="mt-4 w-full rounded-2xl border border-slate-200 p-3 dark:border-neutral-700 dark:bg-neutral-950" placeholder="Motivo…"></textarea>
                @error('motivoNoAplica') <p class="mt-1 text-sm font-bold text-rose-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="cerrarNoAplica" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600">Cancelar</button>
                    <button type="button" wire:click="guardarNoAplica" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white">Guardar justificación</button>
                </div>
            </div>
        </div>
    @endif
</div>
