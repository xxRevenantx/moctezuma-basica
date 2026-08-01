<div
    class="space-y-6"
    x-data="directorioTutoresPersistencia({
        userId: @js(auth()->id()),
        scope: @js($slug_nivel),
    })"
>
    <span class="hidden" data-directorio-current-page="{{ $filas->currentPage() }}"></span>

    <div class="relative overflow-hidden rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-emerald-50 p-5 dark:border-sky-900/40 dark:from-sky-950/20 dark:via-neutral-900 dark:to-emerald-950/20 sm:p-6">
        <div class="pointer-events-none absolute -right-16 -top-16 h-52 w-52 rounded-full bg-sky-300/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-sky-700 dark:border-sky-900/50 dark:bg-neutral-900/70 dark:text-sky-300">
                    <flux:icon.users class="h-4 w-4" />
                    Información familiar
                </div>
                <h3 class="mt-3 text-2xl font-black text-slate-900 dark:text-white">Directorio de padres y tutores</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Consulta solamente alumnos activos y genera directorios institucionales en PDF o Word. Los alumnos sin responsable, teléfono o domicilio se conservan con una leyenda para facilitar su corrección posterior.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="restoreStoredState(false)" :disabled="restoring || !hasStoredState"
                    class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-white px-4 py-2.5 text-xs font-black text-sky-700 shadow-sm transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-45 dark:border-sky-900/50 dark:bg-neutral-900 dark:text-sky-300">
                    <flux:icon.arrow-path class="h-4 w-4" />
                    Restablecer vista guardada
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
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Nivel</label>
                <select wire:model.live="nivel_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    @foreach ($niveles as $item)
                        <option value="{{ $item['id'] }}">{{ $item['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Ciclo escolar</label>
                <select wire:model.live="ciclo_escolar_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="">Todos los ciclos</option>
                    @foreach ($ciclosEscolares as $ciclo)
                        <option value="{{ $ciclo['id'] }}">{{ $ciclo['nombre'] }}{{ $ciclo['es_actual'] ? ' · Actual' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Generación</label>
                <select wire:model.live="generacion_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="">Todas las generaciones</option>
                    @foreach ($generaciones as $generacion)
                        <option value="{{ $generacion['id'] }}">{{ $generacion['nombre'] }}{{ $generacion['status'] ? '' : ' · Histórica' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Grado</label>
                <select wire:model.live="grado_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="">Todos los grados</option>
                    @foreach ($grados as $grado)
                        <option value="{{ $grado['id'] }}">{{ $grado['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            @if (count($semestres) > 0)
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Semestre</label>
                    <select wire:model.live="semestre_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                        <option value="">Todos los semestres</option>
                        @foreach ($semestres as $semestre)
                            <option value="{{ $semestre['id'] }}">{{ $semestre['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Grupo</label>
                <select wire:model.live="grupo_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="">Todos los grupos</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo['id'] }}">{{ $grupo['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Responsables</label>
                <select wire:model.live="modo_responsables" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="principal">Solamente tutor principal</option>
                    <option value="todos">Todos los responsables activos</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Parentesco</label>
                <select wire:model.live="parentesco" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="">Todos los parentescos</option>
                    @foreach ($parentescos as $item)
                        <option value="{{ $item }}">{{ \Illuminate\Support\Str::headline($item) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Buscar</label>
                <div class="relative">
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input wire:model.live.debounce.350ms="buscar" type="search"
                        placeholder="Alumno, tutor, parentesco, teléfono, domicilio…"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Orden</label>
                <select wire:model.live="orden" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="academico_alumno">Grado, grupo y alumno</option>
                    <option value="alumno">Apellido del alumno</option>
                    <option value="tutor">Apellido del tutor</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Filas por página</label>
                <select wire:model.live="perPage" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="20">20</option>
                    <option value="40">40</option>
                    <option value="80">80</option>
                </select>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-5 dark:border-neutral-800 lg:flex-row lg:items-center lg:justify-between">
            <label class="inline-flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-neutral-700 dark:bg-neutral-950">
                <input wire:model.live="salto_grupo" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <span>
                    <span class="block text-sm font-black text-slate-800 dark:text-white">Página nueva por grado y grupo</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Se aplica a las descargas consolidadas en PDF y Word.</span>
                </span>
            </label>

            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 transition hover:border-rose-200 hover:text-rose-600 dark:border-neutral-700 dark:text-slate-300">
                <flux:icon.x-mark class="h-4 w-4" />
                Limpiar filtros
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['Alumnos', $metricas['alumnos'], 'users', 'text-sky-700 bg-sky-50 dark:bg-sky-950/30 dark:text-sky-300'],
            ['Responsables', $metricas['responsables'], 'user-group', 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/30 dark:text-emerald-300'],
            ['Filas', $metricas['filas'], 'queue-list', 'text-indigo-700 bg-indigo-50 dark:bg-indigo-950/30 dark:text-indigo-300'],
            ['Sin tutor', $metricas['sin_tutor'], 'exclamation-triangle', 'text-rose-700 bg-rose-50 dark:bg-rose-950/30 dark:text-rose-300'],
            ['Sin teléfono', $metricas['sin_telefono'], 'phone', 'text-amber-700 bg-amber-50 dark:bg-amber-950/30 dark:text-amber-300'],
            ['Sin domicilio', $metricas['sin_domicilio'], 'map-pin', 'text-orange-700 bg-orange-50 dark:bg-orange-950/30 dark:text-orange-300'],
        ] as [$etiqueta, $valor, $icono, $clase])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-slate-500">{{ $etiqueta }}</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($valor) }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $clase }}">
                        <flux:icon :name="$icono" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    @if ($metricas['sin_tutor'] || $metricas['sin_telefono'] || $metricas['sin_domicilio'])
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-black">Hay información familiar pendiente</p>
                    <p class="mt-1 text-sm leading-6">
                        La descarga seguirá disponible. Los campos faltantes aparecerán como “Sin tutor registrado”, “Sin teléfono registrado” o “Sin domicilio registrado”.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-600 dark:text-sky-300">Descargas</p>
                <h4 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Documento consolidado o archivos separados por grupo</h4>
                <p class="mt-1 text-sm text-slate-500">PDF en carta vertical; Word conserva el formato amplio, con encabezado institucional, numeración y espacios de firma.</p>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ $urlsDescarga['pdf'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-rose-600 px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-rose-700 {{ $metricas['filas'] === 0 ? 'pointer-events-none opacity-40' : '' }}">
                    <flux:icon.arrow-top-right-on-square class="h-4 w-4" /> Abrir PDF
                </a>
                <a href="{{ $urlsDescarga['word'] }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-blue-700 {{ $metricas['filas'] === 0 ? 'pointer-events-none opacity-40' : '' }}">
                    <flux:icon.document-text class="h-4 w-4" /> Word general
                </a>
                <a href="{{ $urlsDescarga['zip_pdf'] }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300 {{ $metricas['filas'] === 0 ? 'pointer-events-none opacity-40' : '' }}">
                    <flux:icon.archive-box-arrow-down class="h-4 w-4" /> ZIP grupos PDF
                </a>
                <a href="{{ $urlsDescarga['zip_word'] }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300 {{ $metricas['filas'] === 0 ? 'pointer-events-none opacity-40' : '' }}">
                    <flux:icon.archive-box-arrow-down class="h-4 w-4" /> ZIP grupos Word
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950/50 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="font-black text-slate-900 dark:text-white">Vista previa del directorio</h4>
                <p class="mt-1 text-xs text-slate-500">{{ $secciones->count() }} grupo(s) académico(s) encontrado(s).</p>
            </div>
            <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300">
                Solo alumnos activos
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1400px] w-full text-sm">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-3 py-3 text-center">N.º</th>
                        <th class="px-3 py-3 text-left">Padre o tutor</th>
                        <th class="px-3 py-3 text-center">Parentesco</th>
                        <th class="px-3 py-3 text-left">Teléfono</th>
                        <th class="px-3 py-3 text-left">Domicilio</th>
                        <th class="px-3 py-3 text-left">Alumno</th>
                        <th class="px-3 py-3 text-center">Nivel</th>
                        <th class="px-3 py-3 text-center">Grado / semestre</th>
                        <th class="px-3 py-3 text-center">Grupo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-neutral-800">
                    @forelse ($filas as $indice => $fila)
                        <tr class="bg-white align-top transition hover:bg-sky-50/60 dark:bg-neutral-900 dark:hover:bg-sky-950/10">
                            <td class="px-3 py-3 text-center font-black text-slate-500">{{ $filas->firstItem() + $indice }}</td>
                            <td class="px-3 py-3 font-bold {{ $fila['sin_tutor'] ? 'text-rose-600 dark:text-rose-300' : 'text-slate-900 dark:text-white' }}">
                                {{ $fila['responsable'] }}
                                @if ($fila['es_principal'] && !$fila['sin_tutor'])
                                    <span class="ml-1 inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Principal</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center text-slate-600 dark:text-slate-300">{{ $fila['parentesco'] }}</td>
                            <td class="px-3 py-3 {{ $fila['sin_telefono'] ? 'font-bold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300' }}">{{ $fila['telefono'] }}</td>
                            <td class="max-w-[330px] px-3 py-3 leading-5 {{ $fila['sin_domicilio'] ? 'font-bold text-orange-700 dark:text-orange-300' : 'text-slate-600 dark:text-slate-300' }}">{{ $fila['domicilio'] }}</td>
                            <td class="px-3 py-3 font-bold text-slate-900 dark:text-white">{{ $fila['alumno'] }}</td>
                            <td class="px-3 py-3 text-center text-slate-600 dark:text-slate-300">{{ $fila['nivel'] }}</td>
                            <td class="px-3 py-3 text-center text-slate-600 dark:text-slate-300">{{ collect([$fila['grado'], $fila['semestre']])->filter()->join(' · ') }}</td>
                            <td class="px-3 py-3 text-center font-black text-slate-700 dark:text-slate-200">{{ $fila['grupo'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center text-slate-500">
                                <flux:icon.user-group class="mx-auto h-10 w-10 text-slate-300" />
                                <p class="mt-3 font-black">No hay alumnos activos con estos filtros.</p>
                                <p class="mt-1 text-xs">Modifica el ciclo, la generación, el grado o el grupo.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($filas->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
                {{ $filas->links() }}
            </div>
        @endif
    </div>

    <script>
        window.directorioTutoresPersistencia = window.directorioTutoresPersistencia || function(config) {
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

                    if (this.hasStoredState) {
                        this.$nextTick(() => this.restoreStoredState(true));
                    }
                },

                storageKey() {
                    return `moctezuma:directorio-tutores:v1:user:${config.userId}:scope:${config.scope}`;
                },

                removeStoredState() {
                    try {
                        localStorage.removeItem(this.storageKey());
                    } catch (error) {
                        // El navegador puede bloquear el almacenamiento; la vista sigue funcionando sin persistencia.
                    }
                },

                bindWatchers() {
                    [
                        'nivel_id', 'generacion_id', 'ciclo_escolar_id', 'grado_id',
                        'semestre_id', 'grupo_id', 'modo_responsables', 'parentesco',
                        'buscar', 'orden', 'salto_grupo', 'perPage',
                    ].forEach(property => {
                        this.$wire.$watch(property, () => this.scheduleSave());
                    });
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
                        modo_responsables: this.$wire.get('modo_responsables'),
                        parentesco: this.$wire.get('parentesco'),
                        buscar: this.$wire.get('buscar'),
                        orden: this.$wire.get('orden'),
                        salto_grupo: this.$wire.get('salto_grupo'),
                        perPage: this.$wire.get('perPage'),
                        page: this.currentPage(),
                    };
                },

                scheduleSave() {
                    if (this.restoring || Date.now() < this.suppressUntil) {
                        return;
                    }

                    window.clearTimeout(this.saveTimer);
                    this.saveTimer = window.setTimeout(() => this.saveState(), 450);
                },

                saveState() {
                    if (this.restoring || Date.now() < this.suppressUntil) {
                        return;
                    }

                    try {
                        const now = Date.now();
                        localStorage.setItem(this.storageKey(), JSON.stringify({
                            version: 1,
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
                        if (packet?.version !== 1 || !packet?.state || Number(packet.expiresAt || 0) <= Date.now()) {
                            this.removeStoredState();
                            return null;
                        }

                        return packet;
                    } catch (error) {
                        this.removeStoredState();
                        return null;
                    }
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
                        this.toast('success', 'La vista guardada fue eliminada.');
                    } finally {
                        this.restoring = false;
                    }
                },

                toast(icon, title) {
                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon,
                            title,
                            showConfirmButton: false,
                            timer: 2400,
                            timerProgressBar: true,
                        });
                    }
                },
            };
        };
    </script>
</div>
