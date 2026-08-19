<div
    class="space-y-6"
    x-data="directorioTutoresPersistenciaGlobal({ userId: @js(auth()->id()) })"
>
    <span class="hidden" data-directorio-current-page="{{ $registros->currentPage() }}"></span>

    @php
        $nivelActual = collect($niveles)->firstWhere('id', (int) $nivel_id);
        $esBachilleratoVista = $nivelActual && \Illuminate\Support\Str::contains(
            \Illuminate\Support\Str::lower(($nivelActual['slug'] ?? '') . ' ' . ($nivelActual['nombre'] ?? '')),
            'bachillerato'
        );
        $pestanas = [
            'todos' => ['texto' => 'Todos', 'conteo' => $metricas['familias'] ?? 0],
            'multinivel' => ['texto' => 'Familias multinivel', 'conteo' => $metricas['multinivel'] ?? 0],
            'duplicados' => ['texto' => 'Posibles duplicados', 'conteo' => $metricas['duplicados'] ?? 0],
            'incompletos' => ['texto' => 'Datos incompletos', 'conteo' => null],
        ];
    @endphp

    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-8">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-cyan-500 to-[#88AC2E]"></div>
        <div class="pointer-events-none absolute -right-20 -top-20 h-60 w-60 rounded-full bg-cyan-300/15 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-4xl">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <flux:badge color="blue" size="sm">Módulo global</flux:badge>
                    <flux:badge color="green" size="sm">Ciclo actual por defecto</flux:badge>
                    @if (!$nivel_id)
                        <flux:badge color="purple" size="sm">Todos los niveles</flux:badge>
                    @endif
                </div>

                <flux:heading size="xl">Directorio de padres y tutores</flux:heading>
                <flux:text variant="subtle" class="mt-2 max-w-3xl">
                    Consulta responsables de toda la institución, identifica familias con varios hijos o en distintos niveles y revisa posibles registros duplicados sin fusionarlos automáticamente.
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="restoreStoredState(false)" :disabled="restoring || !hasStoredState"
                    class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-white px-4 py-2.5 text-xs font-black text-sky-700 shadow-sm transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-45 dark:border-sky-900/50 dark:bg-neutral-900 dark:text-sky-300">
                    <flux:icon.arrow-path class="h-4 w-4" />
                    Restablecer vista
                </button>
                <button type="button" @click="clearStoredState()" :disabled="restoring"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-600 shadow-sm transition hover:border-rose-200 hover:text-rose-600 disabled:opacity-45 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300">
                    <flux:icon.trash class="h-4 w-4" />
                    Limpiar vista guardada
                </button>
            </div>
        </div>

        <div class="relative mt-4 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <flux:icon.circle-stack class="h-4 w-4" />
            <span x-text="restoring ? 'Restaurando preferencias…' : (hasStoredState ? 'Filtros guardados en este navegador durante 7 días.' : 'La vista se guardará al modificar un filtro.')"></span>
        </div>
    </section>

    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div wire:loading.flex
            wire:target="nivel_id,generacion_id,ciclo_escolar_id,grado_id,semestre_id,grupo_id,estado_alumno,modo_responsables,parentesco,buscar,orden,vista,pestana,tipo_familia,filtro_rapido,perPage,limpiarFiltros,cambiarPestana,cambiarVista,aplicarFiltroRapido"
            class="absolute inset-0 z-40 hidden items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-xl dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Actualizando directorio…
            </div>
        </div>

        <div class="border-b border-slate-200 bg-slate-50/70 p-5 dark:border-neutral-800 dark:bg-neutral-950/30 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">
                        <flux:icon.adjustments-horizontal class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Filtros institucionales</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Selecciona un nivel para habilitar generación, grado y grupo. En “Todos los niveles” la consulta permanece global.</p>
                    </div>
                </div>

                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                    <flux:icon.x-mark class="h-4 w-4" />
                    Limpiar filtros
                </button>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <div class="mb-5 rounded-2xl border border-violet-200 bg-violet-50/60 p-4 text-sm dark:border-violet-900/40 dark:bg-violet-950/20">
                <div class="flex items-start gap-3">
                    <flux:icon.information-circle class="mt-0.5 h-5 w-5 shrink-0 text-violet-600 dark:text-violet-300" />
                    <div class="text-violet-900 dark:text-violet-100">
                        <p class="font-black">Responsable repetido no significa duplicado.</p>
                        <p class="mt-1 text-xs font-semibold leading-5 text-violet-700 dark:text-violet-300">
                            Un mismo responsable puede estar correctamente relacionado con varios alumnos. “MULTINIVEL” indica hijos en dos o más niveles; “Posible duplicado” señala dos registros de tutor que parecen representar a la misma persona y requieren revisión.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <flux:field>
                    <flux:label>Nivel</flux:label>
                    <flux:select wire:model.live="nivel_id">
                        <flux:select.option value="">Todos los niveles</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Ciclo escolar</flux:label>
                    <flux:select wire:model.live="ciclo_escolar_id">
                        <flux:select.option value="">Todos los ciclos</flux:select.option>
                        @foreach ($ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo['id'] }}">
                                {{ $ciclo['nombre'] }}{{ $ciclo['es_actual'] ? ' · Actual' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Estado del alumno</flux:label>
                    <flux:select wire:model.live="estado_alumno">
                        <flux:select.option value="activos">Activos</flux:select.option>
                        <flux:select.option value="egresados">Egresados</flux:select.option>
                        <flux:select.option value="no_reinscritos">No reinscritos / pendientes</flux:select.option>
                        <flux:select.option value="todos">Todos</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Generación</flux:label>
                    <flux:select wire:model.live="generacion_id" :disabled="!$nivel_id">
                        <flux:select.option value="">Todas las generaciones</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ $generacion['id'] }}">{{ $generacion['nombre'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="grado_id" :disabled="!$nivel_id">
                        <flux:select.option value="">Todos los grados</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado['id'] }}">{{ $grado['nombre'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                @if ($esBachilleratoVista)
                    <flux:field>
                        <flux:label>Semestre</flux:label>
                        <flux:select wire:model.live="semestre_id" :disabled="!$grado_id">
                            <flux:select.option value="">Todos los semestres</flux:select.option>
                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ $semestre['id'] }}">{{ $semestre['nombre'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.live="grupo_id" :disabled="!$nivel_id">
                        <flux:select.option value="">Todos los grupos</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ $grupo['id'] }}">{{ $grupo['nombre'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Responsables</flux:label>
                    <flux:select wire:model.live="modo_responsables">
                        <flux:select.option value="principal">Solamente tutor principal</flux:select.option>
                        <flux:select.option value="todos">Todos los responsables activos</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Parentesco</flux:label>
                    <flux:select wire:model.live="parentesco">
                        <flux:select.option value="">Todos los parentescos</flux:select.option>
                        @foreach ($parentescos as $item)
                            <flux:select.option value="{{ $item }}">{{ \Illuminate\Support\Str::headline($item) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de familia</flux:label>
                    <flux:select wire:model.live="tipo_familia">
                        <flux:select.option value="todas">Todas</flux:select.option>
                        <flux:select.option value="uno">Un alumno</flux:select.option>
                        <flux:select.option value="varios">Varios alumnos</flux:select.option>
                        <flux:select.option value="multinivel">Multinivel</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Buscar</flux:label>
                    <flux:input wire:model.live.debounce.450ms="buscar" icon="magnifying-glass"
                        placeholder="Alumno, tutor, matrícula, CURP, teléfono, INE, domicilio, nivel…" />
                </flux:field>

                <flux:field>
                    <flux:label>Orden</flux:label>
                    <flux:select wire:model.live="orden">
                        <flux:select.option value="academico_alumno">Nivel, grado, grupo y alumno</flux:select.option>
                        <flux:select.option value="alumno">Alumno</flux:select.option>
                        <flux:select.option value="tutor">Padre o tutor</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Filas por página</flux:label>
                    <flux:select wire:model.live="perPage">
                        <flux:select.option value="20">20</flux:select.option>
                        <flux:select.option value="40">40</flux:select.option>
                        <flux:select.option value="80">80</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>

            <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-5 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                <label class="inline-flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-slate-200">
                    <input type="checkbox" wire:model.live="salto_grupo" class="rounded border-slate-300 text-[#006492] focus:ring-[#006492]" @disabled($vista !== 'alumnos')>
                    <span>
                        Página nueva por grado y grupo
                        <small class="block font-medium text-slate-500">Solo aplica a descargas por alumnos.</small>
                    </span>
                </label>

                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    El INE se muestra con el folio registrado del documento “INE del responsable”; si no existe, la columna queda vacía.
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-wrap gap-2">
            @foreach ($pestanas as $clave => $config)
                <button type="button" wire:click="cambiarPestana('{{ $clave }}')"
                    class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition {{ $pestana === $clave ? 'bg-[#006492] text-white shadow-md' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800' }}">
                    {{ $config['texto'] }}
                    @if ($config['conteo'] !== null)
                        <span class="rounded-full px-2 py-0.5 text-[11px] {{ $pestana === $clave ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300' }}">
                            {{ $config['conteo'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 md:grid-cols-5 xl:grid-cols-10">
        @php
            $cards = [
                ['label' => 'Alumnos', 'value' => $metricas['alumnos'] ?? 0, 'filter' => '', 'tone' => 'sky'],
                ['label' => 'Familias', 'value' => $metricas['familias'] ?? 0, 'filter' => '', 'tone' => 'emerald'],
                ['label' => 'Responsables', 'value' => $metricas['responsables'] ?? 0, 'filter' => '', 'tone' => 'indigo'],
                ['label' => 'Varios hijos', 'value' => $metricas['varios_hijos'] ?? 0, 'filter' => 'varios_hijos', 'tone' => 'violet'],
                ['label' => 'Multinivel', 'value' => $metricas['multinivel'] ?? 0, 'filter' => 'multinivel', 'tone' => 'purple'],
                ['label' => 'Sin tutor', 'value' => $metricas['sin_tutor'] ?? 0, 'filter' => 'sin_tutor', 'tone' => 'rose'],
                ['label' => 'Sin teléfono', 'value' => $metricas['sin_telefono'] ?? 0, 'filter' => 'sin_telefono', 'tone' => 'amber'],
                ['label' => 'Sin domicilio', 'value' => $metricas['sin_domicilio'] ?? 0, 'filter' => 'sin_domicilio', 'tone' => 'orange'],
                ['label' => 'Sin CURP', 'value' => $metricas['sin_curp'] ?? 0, 'filter' => 'sin_curp', 'tone' => 'yellow'],
                ['label' => 'Duplicados', 'value' => $metricas['duplicados'] ?? 0, 'filter' => 'duplicados', 'tone' => 'red'],
            ];
        @endphp

        @foreach ($cards as $card)
            @if ($card['filter'])
                <button type="button" wire:click="aplicarFiltroRapido('{{ $card['filter'] }}')"
                    class="rounded-2xl border p-4 text-left shadow-sm transition hover:-translate-y-0.5 {{ $filtro_rapido === $card['filter'] ? 'border-[#006492] bg-sky-50 ring-2 ring-sky-100 dark:bg-sky-950/30 dark:ring-sky-900/30' : 'border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900' }}">
                    <span class="block text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</span>
                    <span class="mt-1 block text-2xl font-black text-slate-900 dark:text-white">{{ $card['value'] }}</span>
                </button>
            @else
                <button type="button" wire:click="aplicarFiltroRapido('')"
                    class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 dark:border-neutral-800 dark:bg-neutral-900">
                    <span class="block text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</span>
                    <span class="mt-1 block text-2xl font-black text-slate-900 dark:text-white">{{ $card['value'] }}</span>
                </button>
            @endif
        @endforeach
    </section>

    @if (($pestana === 'duplicados' || $filtro_rapido === 'duplicados') && $duplicados->isNotEmpty())
        <section class="overflow-hidden rounded-3xl border border-rose-200 bg-white shadow-sm dark:border-rose-900/40 dark:bg-neutral-900">
            <div class="border-b border-rose-200 bg-rose-50/70 p-5 dark:border-rose-900/40 dark:bg-rose-950/20 sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
                        <flux:icon.exclamation-triangle class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-black text-rose-950 dark:text-rose-100">Revisión de posibles responsables duplicados</h2>
                        <p class="mt-1 text-sm text-rose-700 dark:text-rose-300">El sistema solo detecta coincidencias; no fusiona ni elimina registros automáticamente.</p>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-neutral-800">
                @foreach ($duplicados as $grupoDuplicado)
                    <div class="p-5 sm:p-6" x-data="{ abierto: {{ $loop->first ? 'true' : 'false' }} }">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-black uppercase {{ $grupoDuplicado['tipo'] === 'curp_exacta' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                        {{ $grupoDuplicado['tipo'] === 'curp_exacta' ? 'Coincidencia fuerte' : 'Requiere revisión' }}
                                    </span>
                                    <span class="text-sm font-black text-slate-900 dark:text-white">{{ $grupoDuplicado['etiqueta'] }}</span>
                                </div>
                                <p class="mt-2 text-xs font-semibold text-slate-500">{{ $grupoDuplicado['tutor_ids']->count() }} registros de tutor relacionados.</p>
                            </div>

                            <button type="button" @click="abierto = !abierto"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 dark:border-neutral-700 dark:text-slate-300">
                                <flux:icon.eye class="h-4 w-4" />
                                <span x-text="abierto ? 'Ocultar comparación' : 'Comparar registros'"></span>
                            </button>
                        </div>

                        <div x-cloak x-show="abierto" x-transition.opacity.duration.200ms class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($grupoDuplicado['tutores'] as $tutorDuplicado)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-700 dark:bg-neutral-950/40">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white">{{ $tutorDuplicado['nombre'] }}</p>
                                            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Registro #{{ $tutorDuplicado['id'] }}</p>
                                        </div>
                                        @if ($grupoDuplicado['tipo'] === 'curp_exacta')
                                            <flux:icon.exclamation-triangle class="h-5 w-5 text-rose-500" />
                                        @endif
                                    </div>
                                    <dl class="mt-3 space-y-2 text-xs">
                                        <div><dt class="inline font-black text-slate-500">CURP:</dt> <dd class="inline text-slate-700 dark:text-slate-300">{{ $tutorDuplicado['curp'] ?: 'Sin CURP' }}</dd></div>
                                        <div><dt class="inline font-black text-slate-500">Teléfono:</dt> <dd class="inline text-slate-700 dark:text-slate-300">{{ $tutorDuplicado['telefono'] }}</dd></div>
                                        <div><dt class="inline font-black text-slate-500">Alumnos:</dt> <dd class="inline text-slate-700 dark:text-slate-300">{{ $tutorDuplicado['alumnos']->join(', ') }}</dd></div>
                                        <div><dt class="inline font-black text-slate-500">Niveles:</dt> <dd class="inline text-slate-700 dark:text-slate-300">{{ $tutorDuplicado['niveles']->join(' · ') }}</dd></div>
                                    </dl>
                                    <a href="{{ route('misrutas.tutores', ['buscar' => $tutorDuplicado['nombre']]) }}" wire:navigate
                                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-[#006492] hover:underline">
                                        Ver relaciones del responsable
                                        <flux:icon.arrow-right class="h-3.5 w-3.5" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 dark:border-neutral-800 sm:p-6 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Directorio</h2>
                    <flux:badge color="blue" size="sm">{{ $registros->total() }} registros</flux:badge>
                </div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ $vista === 'familias' ? 'Una fila por responsable/familia, con sus alumnos relacionados.' : 'Una fila por alumno y responsable relacionado.' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1 dark:border-neutral-700 dark:bg-neutral-950/40">
                    <button type="button" wire:click="cambiarVista('familias')"
                        class="rounded-xl px-3 py-2 text-xs font-black transition {{ $vista === 'familias' ? 'bg-white text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300' : 'text-slate-500' }}">
                        Familias
                    </button>
                    <button type="button" wire:click="cambiarVista('alumnos')"
                        class="rounded-xl px-3 py-2 text-xs font-black transition {{ $vista === 'alumnos' ? 'bg-white text-[#006492] shadow-sm dark:bg-neutral-800 dark:text-sky-300' : 'text-slate-500' }}">
                        Alumnos
                    </button>
                </div>

                <a href="{{ $urlsDescarga['pdf'] }}" target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#006492] px-3.5 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-[#00557c]">
                    <flux:icon.document-arrow-down class="h-4 w-4" /> PDF
                </a>
                <a href="{{ $urlsDescarga['word'] }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2.5 text-xs font-black text-sky-700 transition hover:bg-sky-100 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                    <flux:icon.document-text class="h-4 w-4" /> Word
                </a>

                @if ($vista === 'alumnos')
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" @click.outside="open = false"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            Por grupos
                            <flux:icon.chevron-down class="h-3.5 w-3.5" />
                        </button>
                        <div x-cloak x-show="open" x-transition class="absolute right-0 z-30 mt-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                            <a href="{{ $urlsDescarga['zip_pdf'] }}" class="block rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-neutral-800">ZIP de PDF por grupos</a>
                            <a href="{{ $urlsDescarga['zip_word'] }}" class="block rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-neutral-800">ZIP de Word por grupos</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($vista === 'familias')
            <div class="divide-y divide-slate-200 dark:divide-neutral-800">
                @forelse ($registros as $familia)
                    <article class="p-5 sm:p-6" x-data="{ abierto: false }">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ $familia['responsable'] }}</h3>
                                    @if ($familia['varios_hijos'])
                                        <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[10px] font-black text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">{{ $familia['alumnos_count'] }} alumnos</span>
                                    @endif
                                    @if ($familia['multinivel'])
                                        <span class="rounded-full bg-purple-600 px-2.5 py-1 text-[10px] font-black text-white">MULTINIVEL · {{ $familia['niveles_count'] }} niveles</span>
                                    @endif
                                    @if ($familia['unidad_familiar_compartida'])
                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-black text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">Unidad familiar compartida</span>
                                    @endif
                                    @if ($familia['posible_duplicado'])
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-[10px] font-black text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Posible duplicado</span>
                                    @endif
                                    @if ($familia['tutor_ids']->count() > 1)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">{{ $familia['tutor_ids']->count() }} registros de tutor</span>
                                    @endif
                                </div>

                                <div class="mt-3 grid gap-x-6 gap-y-2 text-xs sm:grid-cols-2 lg:grid-cols-4">
                                    <div><span class="font-black text-slate-500">Parentesco:</span> <span class="text-slate-700 dark:text-slate-300">{{ $familia['parentesco'] ?: '—' }}</span></div>
                                    <div><span class="font-black text-slate-500">Teléfono:</span> <span class="{{ $familia['sin_telefono'] ? 'font-bold text-amber-700 dark:text-amber-300' : 'text-slate-700 dark:text-slate-300' }}">{{ $familia['telefono'] ?: 'Sin teléfono registrado' }}</span></div>
                                    <div><span class="font-black text-slate-500">INE:</span> <span class="text-slate-700 dark:text-slate-300">{{ $familia['ine'] ?: '—' }}</span></div>
                                    <div><span class="font-black text-slate-500">Niveles:</span> <span class="text-slate-700 dark:text-slate-300">{{ $familia['niveles_texto'] ?: '—' }}</span></div>
                                </div>
                                <p class="mt-2 text-xs leading-5 {{ $familia['sin_domicilio'] ? 'font-bold text-orange-700 dark:text-orange-300' : 'text-slate-500 dark:text-slate-400' }}">
                                    <span class="font-black">Domicilio:</span> {{ $familia['domicilio'] ?: 'Sin domicilio registrado' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if ($familia['tutor_id'])
                                    @if (auth()->user()?->canAccess('alumnos.editar'))
                                        <button type="button"
                                            @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ (int) $familia['tutor_id'] }} });"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                                            <flux:icon.pencil-square class="h-4 w-4" /> Editar tutor
                                        </button>
                                    @endif
                                    <button type="button"
                                        @click="Livewire.dispatch('abrir-expediente-tutor', { tutorId: {{ (int) $familia['tutor_id'] }} })"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        <flux:icon.folder-open class="h-4 w-4" /> Expediente
                                    </button>
                                @endif
                                <button type="button" @click="abierto = !abierto"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#006492] px-3 py-2 text-xs font-black text-white hover:bg-[#00557c]">
                                    <flux:icon.users class="h-4 w-4" />
                                    <span x-text="abierto ? 'Ocultar alumnos' : 'Ver alumnos'"></span>
                                </button>
                            </div>
                        </div>

                        <div x-cloak x-show="abierto" x-collapse class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-700">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-950/50">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Alumno</th>
                                            <th class="px-4 py-3 text-left">Matrícula</th>
                                            <th class="px-4 py-3 text-center">Nivel</th>
                                            <th class="px-4 py-3 text-center">Grado / semestre</th>
                                            <th class="px-4 py-3 text-center">Grupo</th>
                                            <th class="px-4 py-3 text-center">Estado</th>
                                            <th class="px-4 py-3 text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                                        @foreach ($familia['alumnos'] as $alumno)
                                            <tr>
                                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $alumno['nombre'] }}</td>
                                                <td class="px-4 py-3 text-slate-500">{{ $alumno['matricula'] ?: '—' }}</td>
                                                <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-300">{{ $alumno['nivel'] }}</td>
                                                <td class="px-4 py-3 text-center text-slate-600 dark:text-slate-300">{{ collect([$alumno['grado'], $alumno['semestre']])->filter()->join(' · ') }}</td>
                                                <td class="px-4 py-3 text-center font-bold text-slate-700 dark:text-slate-200">{{ $alumno['grupo'] }}</td>
                                                <td class="px-4 py-3 text-center"><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ $alumno['estatus_etiqueta'] }}</span></td>
                                                <td class="px-4 py-3">
                                                    <div class="flex justify-end gap-2">
                                                        <a href="{{ route('misrutas.alumnos.expediente-360', ['inscripcion' => $alumno['id']]) }}" wire:navigate class="text-xs font-black text-[#006492] hover:underline">Ver alumno</a>
                                                        @if ($alumno['nivel_slug'])
                                                            <a href="{{ route('misrutas.matricula.editar', ['slug_nivel' => $alumno['nivel_slug'], 'inscripcion' => $alumno['id']]) }}" wire:navigate class="text-xs font-black text-emerald-700 hover:underline dark:text-emerald-300">Editar matrícula</a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-16 text-center text-slate-500">
                        <flux:icon.user-group class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-3 font-black">No hay familias con estos filtros.</p>
                        <p class="mt-1 text-xs">Prueba otro nivel, estado, ciclo o tipo de familia.</p>
                    </div>
                @endforelse
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[1450px] w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wide text-slate-500 dark:bg-neutral-950/50">
                        <tr>
                            <th class="px-3 py-3 text-center">N.º</th>
                            <th class="px-3 py-3 text-left">Padre o tutor</th>
                            <th class="px-3 py-3 text-center">Parentesco</th>
                            <th class="px-3 py-3 text-left">Teléfono</th>
                            <th class="px-3 py-3 text-left">INE</th>
                            <th class="px-3 py-3 text-left">Domicilio</th>
                            <th class="px-3 py-3 text-left">Alumno</th>
                            <th class="px-3 py-3 text-center">Nivel</th>
                            <th class="px-3 py-3 text-center">Grado / semestre</th>
                            <th class="px-3 py-3 text-center">Grupo</th>
                            <th class="px-3 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                        @forelse ($registros as $indice => $fila)
                            <tr class="align-top transition hover:bg-sky-50/50 dark:hover:bg-sky-950/10">
                                <td class="px-3 py-3 text-center font-black text-slate-500">{{ $registros->firstItem() + $indice }}</td>
                                <td class="px-3 py-3">
                                    <div class="font-bold {{ $fila['sin_tutor'] ? 'text-rose-600 dark:text-rose-300' : 'text-slate-900 dark:text-white' }}">{{ $fila['responsable'] }}</div>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @if ($fila['es_principal'] && !$fila['sin_tutor'])
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Principal</span>
                                        @endif
                                        @if ($fila['posible_duplicado'])
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">Posible duplicado</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center text-slate-600 dark:text-slate-300">{{ $fila['parentesco'] }}</td>
                                <td class="px-3 py-3 {{ $fila['sin_telefono'] ? 'font-bold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300' }}">{{ $fila['telefono'] }}</td>
                                <td class="px-3 py-3 text-slate-600 dark:text-slate-300">{{ $fila['ine'] ?: '—' }}</td>
                                <td class="max-w-[320px] px-3 py-3 leading-5 {{ $fila['sin_domicilio'] ? 'font-bold text-orange-700 dark:text-orange-300' : 'text-slate-600 dark:text-slate-300' }}">{{ $fila['domicilio'] }}</td>
                                <td class="px-3 py-3">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $fila['alumno'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $fila['matricula'] ?: 'Sin matrícula' }}</p>
                                </td>
                                <td class="px-3 py-3 text-center text-slate-600 dark:text-slate-300">{{ $fila['nivel'] }}</td>
                                <td class="px-3 py-3 text-center text-slate-600 dark:text-slate-300">{{ collect([$fila['grado'], $fila['semestre']])->filter()->join(' · ') }}</td>
                                <td class="px-3 py-3 text-center font-black text-slate-700 dark:text-slate-200">{{ $fila['grupo'] }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('misrutas.alumnos.expediente-360', ['inscripcion' => $fila['alumno_id']]) }}" wire:navigate class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[11px] font-black text-[#006492] dark:border-neutral-700">Alumno</a>
                                        @if ($fila['tutor_id'])
                                            <button type="button" @click="Livewire.dispatch('abrir-expediente-tutor', { tutorId: {{ (int) $fila['tutor_id'] }} })" class="rounded-lg border border-emerald-200 px-2.5 py-1.5 text-[11px] font-black text-emerald-700 dark:border-emerald-900/50 dark:text-emerald-300">Tutor</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-16 text-center text-slate-500">
                                    <flux:icon.users class="mx-auto h-10 w-10 text-slate-300" />
                                    <p class="mt-3 font-black">No hay alumnos con estos filtros.</p>
                                    <p class="mt-1 text-xs">Modifica el nivel, ciclo, estado o búsqueda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if ($registros->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
                {{ $registros->links() }}
            </div>
        @endif
    </section>

    <livewire:tutor.editar-tutor />
    <livewire:tutor.expediente-tutor />

    <script>
        window.directorioTutoresPersistenciaGlobal = window.directorioTutoresPersistenciaGlobal || function(config) {
            return {
                restoring: false,
                hasStoredState: false,
                saveTimer: null,
                suppressUntil: 0,
                observer: null,
                ttl: 7 * 24 * 60 * 60 * 1000,

                init() {
                    this.hasStoredState = this.readPacket() !== null;
                    this.bindWatchers();
                    this.observePageChanges();
                    if (this.hasStoredState) this.$nextTick(() => this.restoreStoredState(true));
                },

                storageKey() {
                    return `moctezuma:directorio-tutores:v2:user:${config.userId}:scope:global`;
                },

                bindWatchers() {
                    [
                        'nivel_id', 'generacion_id', 'ciclo_escolar_id', 'grado_id', 'semestre_id', 'grupo_id',
                        'estado_alumno', 'modo_responsables', 'parentesco', 'buscar', 'orden', 'vista', 'pestana',
                        'tipo_familia', 'filtro_rapido', 'salto_grupo', 'perPage',
                    ].forEach(property => this.$wire.$watch(property, () => this.scheduleSave()));
                },

                observePageChanges() {
                    this.observer?.disconnect();
                    this.observer = new MutationObserver(() => this.scheduleSave());
                    this.observer.observe(this.$root, { childList: true, subtree: true, attributes: true });
                },

                currentPage() {
                    return Number(this.$root.querySelector('[data-directorio-current-page]')?.dataset.directorioCurrentPage || 1);
                },

                currentState() {
                    return {
                        nivel_id: this.$wire.get('nivel_id'),
                        generacion_id: this.$wire.get('generacion_id'),
                        ciclo_escolar_id: this.$wire.get('ciclo_escolar_id'),
                        grado_id: this.$wire.get('grado_id'),
                        semestre_id: this.$wire.get('semestre_id'),
                        grupo_id: this.$wire.get('grupo_id'),
                        estado_alumno: this.$wire.get('estado_alumno'),
                        modo_responsables: this.$wire.get('modo_responsables'),
                        parentesco: this.$wire.get('parentesco'),
                        buscar: this.$wire.get('buscar'),
                        orden: this.$wire.get('orden'),
                        vista: this.$wire.get('vista'),
                        pestana: this.$wire.get('pestana'),
                        tipo_familia: this.$wire.get('tipo_familia'),
                        filtro_rapido: this.$wire.get('filtro_rapido'),
                        salto_grupo: this.$wire.get('salto_grupo'),
                        perPage: this.$wire.get('perPage'),
                        page: this.currentPage(),
                    };
                },

                scheduleSave() {
                    if (this.restoring || Date.now() < this.suppressUntil) return;
                    window.clearTimeout(this.saveTimer);
                    this.saveTimer = window.setTimeout(() => this.saveState(), 450);
                },

                saveState() {
                    if (this.restoring || Date.now() < this.suppressUntil) return;
                    try {
                        const now = Date.now();
                        localStorage.setItem(this.storageKey(), JSON.stringify({
                            version: 2,
                            savedAt: now,
                            expiresAt: now + this.ttl,
                            state: this.currentState(),
                        }));
                        this.hasStoredState = true;
                    } catch (error) {
                        this.hasStoredState = false;
                    }
                },

                readPacket() {
                    try {
                        const raw = localStorage.getItem(this.storageKey());
                        if (!raw) return null;
                        const packet = JSON.parse(raw);
                        if (packet?.version !== 2 || !packet?.state || Number(packet.expiresAt || 0) <= Date.now()) {
                            this.removeStoredState();
                            return null;
                        }
                        return packet;
                    } catch (error) {
                        this.removeStoredState();
                        return null;
                    }
                },

                removeStoredState() {
                    try { localStorage.removeItem(this.storageKey()); } catch (error) {}
                },

                async restoreStoredState(automatic = false) {
                    const packet = this.readPacket();
                    this.hasStoredState = packet !== null;
                    if (!packet || this.restoring) return;

                    this.restoring = true;
                    window.clearTimeout(this.saveTimer);
                    try {
                        await this.$wire.restaurarVistaGuardada(packet.state);
                        this.suppressUntil = Date.now() + 700;
                        if (!automatic) this.toast('success', 'Vista guardada restaurada.');
                    } catch (error) {
                        this.removeStoredState();
                        this.hasStoredState = false;
                        this.toast('error', 'No fue posible restaurar la vista guardada.');
                    } finally {
                        this.restoring = false;
                    }
                },

                async clearStoredState() {
                    window.clearTimeout(this.saveTimer);
                    this.removeStoredState();
                    this.hasStoredState = false;
                    this.restoring = true;
                    try {
                        await this.$wire.limpiarFiltros();
                        this.suppressUntil = Date.now() + 1000;
                        this.toast('success', 'Vista restablecida.');
                    } finally {
                        this.restoring = false;
                    }
                },

                toast(icon, title) {
                    if (!window.Swal) return;
                    Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2200, timerProgressBar: true });
                },
            };
        };
    </script>
</div>
