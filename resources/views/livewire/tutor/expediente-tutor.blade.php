<div>
    @if ($abierto && $tutor && $resumen)
        @php
            $puedeEditar = auth()->user()?->is_admin
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('alumnos.editar');
        @endphp

        <section
            x-data="{ tab: 'documentos' }"
            class="overflow-hidden rounded-[26px] border border-slate-200 bg-slate-50 shadow-xl ring-1 ring-slate-950/5 dark:border-neutral-800 dark:bg-neutral-950"
        >
            <header class="relative overflow-hidden bg-gradient-to-r from-[#006492] via-sky-700 to-[#88AC2E] px-4 py-4 text-white sm:px-6 sm:py-5">
                <div class="pointer-events-none absolute -right-16 -top-24 size-72 rounded-full bg-white/10 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-black uppercase tracking-wider">
                                Expediente documental del responsable
                            </span>
                            <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase {{ $tutor->activo ? 'bg-emerald-400/90 text-emerald-950' : 'bg-amber-300 text-amber-950' }}">
                                {{ $tutor->activo ? 'Activo' : 'Archivado' }}
                            </span>
                        </div>
                        <h2 class="mt-2 truncate text-xl font-black sm:text-2xl">{{ $tutor->nombre_completo }}</h2>
                        <p class="mt-1 text-xs text-white/80 sm:text-sm">
                            {{ $tutor->identidad_protegida }} · {{ $tutor->telefono_celular ?: ($tutor->telefono_casa ?: 'Sin teléfono') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($puedeEditar)
                            <button type="button" wire:click="abrirOrganizador"
                                class="inline-flex items-center gap-2 rounded-xl bg-violet-500 px-3.5 py-2.5 text-xs font-black text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-violet-400 sm:text-sm">
                                <flux:icon name="squares-plus" class="size-4" /> Organizar páginas
                            </button>
                        @endif
                        <a href="{{ route('misrutas.expedientes-tutores.zip', $tutor) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2.5 text-xs font-black text-sky-800 shadow-lg transition hover:-translate-y-0.5 sm:text-sm">
                            <flux:icon name="archive-box" class="size-4" /> Descargar ZIP
                        </a>
                        <button type="button" wire:click="cerrar"
                            class="inline-flex size-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 transition hover:bg-white/20"
                            title="Contraer expediente" aria-label="Contraer expediente">
                            <flux:icon name="chevron-up" class="size-5" />
                        </button>
                    </div>
                </div>
            </header>

            <div class="grid gap-3 border-b border-slate-200 bg-white px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900 sm:grid-cols-[1fr_auto] sm:items-center sm:px-6">
                <div class="flex flex-wrap gap-2">
                    @foreach (['documentos' => 'Documentos esperados', 'fuentes' => 'Archivos fuente', 'historial' => 'Historial y auditoría', 'configuracion' => 'Configuración'] as $clave => $etiqueta)
                        @if ($clave !== 'configuracion' || auth()->user()?->is_admin)
                            <button type="button" @click="tab = '{{ $clave }}'"
                                :class="tab === '{{ $clave }}' ? 'bg-sky-700 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:hover:bg-neutral-700'"
                                class="rounded-xl px-3.5 py-2 text-xs font-black transition">
                                {{ $etiqueta }}
                            </button>
                        @endif
                    @endforeach
                </div>

                <div class="min-w-[180px]">
                    <div class="mb-1 flex justify-between text-[11px] font-black text-slate-500 dark:text-slate-400">
                        <span>Integridad documental</span>
                        <span>{{ $resumen['completados'] }}/{{ $resumen['total'] }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-700">
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-600 to-[#88AC2E]" style="width: {{ $resumen['porcentaje'] }}%"></div>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-6">
                <div class="mb-5 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800 dark:border-sky-900/60 dark:bg-sky-950/20 dark:text-sky-200">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white">
                            <flux:icon name="users" class="size-4" />
                        </span>
                        <div>
                            <p class="font-black">Una sola fuente documental por responsable</p>
                            <p class="mt-1 text-xs leading-relaxed">
                                Los documentos cargados aquí se reflejan automáticamente en los expedientes de los alumnos relacionados. Al reemplazar un archivo, la versión anterior permanece disponible en el historial.
                            </p>
                        </div>
                    </div>
                </div>

                @if (($resumen['archivos_faltantes'] ?? 0) > 0)
                    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-200">
                        Hay {{ $resumen['archivos_faltantes'] }} registro(s) cuyo archivo físico ya no está disponible. Sube una copia nueva para reparar el expediente.
                    </div>
                @endif

                @if ($pendientes->isNotEmpty())
                    <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-900/50 dark:bg-amber-950/20 sm:p-5">
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
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Alumno: {{ trim(($pendiente->inscripcion?->apellido_paterno ?? '') . ' ' . ($pendiente->inscripcion?->apellido_materno ?? '') . ' ' . ($pendiente->inscripcion?->nombre ?? '')) }}
                                        · {{ $pendiente->inscripcion?->matricula }}
                                    </p>
                                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">{{ $pendiente->motivo }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($pendiente->documentoAlumno?->archivo_existe)
                                            <a href="{{ route('misrutas.expedientes.preview', $pendiente->documentoAlumno) }}" target="_blank" rel="noopener"
                                                class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">Ver archivo</a>
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
                            <livewire:tutor.carga-documento-tutor
                                :tutor-id="$tutor->id"
                                :tipo-documento-id="$item['tipo_id']"
                                :etiqueta="$item['nombre']"
                                :descripcion="$item['descripcion']"
                                :obligatorio="$item['obligatorio']"
                                :solo-lectura="! $puedeEditar"
                                :key="'documento-tutor-' . $tutor->id . '-' . $item['tipo_id']"
                            />
                        @endforeach
                    </section>
                </div>

                <div x-show="tab === 'fuentes'" x-cloak>
                    <section class="rounded-2xl border border-violet-200 bg-violet-50/50 p-4 dark:border-violet-900/40 dark:bg-violet-950/10 sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">Archivos originales y combinados</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Se conservan por separado para reorganizar páginas sin perder el archivo recibido.</p>
                            </div>
                            @if ($puedeEditar && $fuentes->isNotEmpty())
                                <button type="button" wire:click="abrirOrganizador" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white hover:bg-violet-700">Organizar fuentes</button>
                            @endif
                        </div>
                        <div class="mt-5 grid gap-3 lg:grid-cols-2">
                            @forelse ($fuentes as $fuente)
                                <article class="rounded-2xl border border-violet-100 bg-white p-4 dark:border-violet-900/40 dark:bg-neutral-900">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-black text-slate-900 dark:text-white" title="{{ $fuente->nombre_original }}">{{ $fuente->nombre_original }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $fuente->paginas }} página(s) · {{ $fuente->tamano_legible }} · {{ $fuente->created_at?->format('d/m/Y H:i') }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Subió: {{ $fuente->usuario?->name ?? 'Sistema' }}</p>
                                        </div>
                                        @if ($fuente->protegido)
                                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-[9px] font-black uppercase text-amber-700">Solo lectura</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ route('misrutas.expedientes-tutores.fuentes.preview', $fuente) }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">Vista previa</a>
                                        <a href="{{ route('misrutas.expedientes-tutores.fuentes.download', $fuente) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:text-slate-200">Original</a>
                                        @if ($puedeEditar && ! $fuente->protegido)
                                            <button type="button" wire:click="abrirOrganizador({{ $fuente->id }})" class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-black text-white hover:bg-violet-700">Organizar</button>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-violet-200 bg-white p-8 text-center text-sm text-slate-500 dark:bg-neutral-900 lg:col-span-2">Aún no hay archivos fuente.</div>
                            @endforelse
                        </div>
                    </section>
                </div>

                <div x-show="tab === 'historial'" x-cloak>
                    <div class="grid gap-5 xl:grid-cols-2">
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                                <h3 class="font-black text-slate-900 dark:text-white">Todas las versiones documentales</h3>
                            </div>
                            <div class="max-h-[540px] divide-y divide-slate-100 overflow-y-auto dark:divide-neutral-800">
                                @forelse ($documentos as $documento)
                                    <div class="flex items-center justify-between gap-3 p-4">
                                        <div class="min-w-0">
                                            <p class="truncate font-black text-slate-900 dark:text-white">{{ $documento->tipoDocumento?->nombre }} · v{{ $documento->version }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($documento->estado) }} · {{ $documento->created_at?->format('d/m/Y H:i') }} · {{ $documento->usuarioQueSubio?->name ?? 'Sistema' }}</p>
                                        </div>
                                        @if ($documento->archivo_existe)
                                            <a href="{{ route('misrutas.expedientes-tutores.preview', $documento) }}" target="_blank" rel="noopener" class="shrink-0 text-xs font-black text-sky-700 dark:text-sky-300">Ver</a>
                                        @else
                                            <span class="shrink-0 text-[10px] font-black uppercase text-rose-600">No disponible</span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="p-8 text-center text-sm text-slate-500">Sin versiones registradas.</p>
                                @endforelse
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                                <h3 class="font-black text-slate-900 dark:text-white">Bitácora de cambios</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Quién subió, reemplazó, organizó, validó o archivó.</p>
                            </div>
                            <div class="max-h-[540px] divide-y divide-slate-100 overflow-y-auto dark:divide-neutral-800">
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
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                                <h3 class="font-black text-slate-900 dark:text-white">Control de documentos esperados del responsable</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Activa o desactiva tipos y define cuáles cuentan como obligatorios.</p>
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @foreach ($tipos as $tipo)
                                    <div class="space-y-3 p-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white">{{ $tipo['nombre'] }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $tipo['descripcion'] }}</p>
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
            </div>
        </section>
    @endif
</div>
