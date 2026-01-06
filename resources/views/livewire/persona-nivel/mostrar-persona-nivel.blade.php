@php
    // ✅ Ordenar por nivel_id (desde cabecera)
    $porNivelSorted = ($porNivel ?? collect())->sortBy(function ($itemsNivel, $nivelNombre) {
        $first = $itemsNivel?->first();
        $nivelId = (int) ($first?->cabecera?->nivel_id ?? ($first?->nivel_id ?? 999999));
        return $nivelId;
    });
@endphp

<div class="space-y-5" x-data="{
    openLevels: {},
    toggleLevel(key) { this.openLevels[key] = !this.openLevels[key] },
    isLevelOpen(key) { return !!this.openLevels[key] },
    openAllLevels() {
        document.querySelectorAll('[data-nivel-key]').forEach(el => {
            this.openLevels[el.dataset.nivelKey] = true
        })
    },
    closeAllLevels() { this.openLevels = {} },

    openProfe: {},
    toggleProfe(id) { this.openProfe[id] = !this.openProfe[id] },
    isProfeOpen(id) { return !!this.openProfe[id] },
    openAllProfe() {
        document.querySelectorAll('[data-profe]').forEach(el => {
            const id = parseInt(el.dataset.profe || '0', 10);
            if (id) this.openProfe[id] = true
        })
    },
    closeAllProfe() { this.openProfe = {} },

    eliminarAsignacion(id) {
        Swal.fire({
            title: '¿Eliminar?',
            text: `Esta fila se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminar', id))
    }
}">
    <!-- Header -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Personal asignado por nivel
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Secundaria: cards por profesor (drag & drop de cards + drag interno por asignaciones).
            Los demás niveles: tabla + drag & drop.
        </p>
    </div>

    <!-- Toolbar -->
    <div
        class="rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow overflow-hidden">
        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        <div class="p-4 sm:p-5 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-blue-50 dark:bg-blue-900/30 grid place-items-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M16 7a4 4 0 01.88 7.903A5 5 0 1115 7h1z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Listado</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Reordenamiento por nivel (y secundaria con cards).
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                {{-- MODAL PARA REANUDACIONES --}}
                <div>
                    <flux:modal.trigger name="reanudaciones">
                        <flux:button variant="primary"
                            class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 dark:focus:ring-offset-neutral-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Reanudaciones
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:modal name="reanudaciones" flyout variant="floating" class="md:w-lg">
                        <form action="{{ route('misrutas.reanudaciones') }}" class="p-6" target="_blank">
                            <div class="space-y-6">
                                <flux:heading size="lg">Descarga los oficios de Reanudaciones de Labores
                                </flux:heading>
                                <flux:subheading>Formulario para descargar Reanudaciones</flux:subheading>

                                <flux:select label="Nivel" name="nivel_id" required>
                                    <flux:select.option value="">--Selecciona un nivel--</flux:select.option>
                                    @foreach ($niveles as $nivel)
                                        <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select label="Tipo de reanudación" name="tipo_reanudacion" required>
                                    <flux:select.option value="">--Selecciona el tipo de reanudación--
                                    </flux:select.option>
                                    <flux:select.option value="1">Reanudación de Receso de clases(Agosto)
                                    </flux:select.option>
                                    <flux:select.option value="2">Reanudaciones de invierno</flux:select.option>
                                    <flux:select.option value="3">Reanudaciones de primavera</flux:select.option>
                                </flux:select>

                                <flux:input label="Fecha del director" name="fecha_director" type="date" required />
                                <flux:input label="Fecha del docente" name="fecha_docente" type="date" required />

                                <flux:select label="Ciclo escolar" name="ciclo_escolar" required>
                                    <flux:select.option value="">--Selecciona el ciclo escolar--
                                    </flux:select.option>
                                    @foreach ($ciclos as $ciclo)
                                        <flux:select.option value="{{ $ciclo->id }}">
                                            {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:textarea name="copias" label="C.C.P" placeholder="Escribe aquí las copias..." />

                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="filled">Cancelar</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="primary">Generar</flux:button>
                                </div>
                            </div>
                        </form>
                    </flux:modal>
                </div>

                <!-- Search -->
                <div class="w-full sm:w-[380px]">
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path
                                    d="M10 4a6 6 0 104.472 10.03l4.249 4.249 1.414-1.414-4.249-4.249A6 6 0 0010 4zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" />
                            </svg>
                        </span>

                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Buscar por nombre, especialidad, grado, grupo o nivel…"
                            class="w-full rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900
                                   pl-10 pr-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400" />
                    </div>
                </div>

                <!-- Open/close all niveles -->
                <div class="flex gap-2">
                    <button type="button" @click="openAllLevels()"
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                               border border-emerald-200 dark:border-emerald-800
                               bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-950 dark:to-teal-950
                               text-emerald-700 dark:text-emerald-300 font-medium
                               hover:from-emerald-100 hover:to-teal-100 dark:hover:from-emerald-900 dark:hover:to-teal-900
                               shadow-sm hover:shadow-md hover:shadow-emerald-500/10
                               transition-all duration-200
                               focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-2 dark:focus:ring-offset-neutral-900">
                        Abrir todo
                    </button>

                    <button type="button" @click="closeAllLevels()"
                        class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                               border border-slate-200 dark:border-slate-800
                               bg-gradient-to-r from-slate-50 to-gray-50 dark:from-slate-950 dark:to-gray-950
                               text-slate-700 dark:text-slate-300 font-medium
                               hover:from-slate-100 hover:to-gray-100 dark:hover:from-slate-900 dark:hover:to-gray-900
                               shadow-sm hover:shadow-md hover:shadow-slate-500/10
                               transition-all duration-200
                               focus:outline-none focus:ring-2 focus:ring-slate-500/50 focus:ring-offset-2 dark:focus:ring-offset-neutral-900">
                        Cerrar todo
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
       NIVELES (ordenados por nivel_id)
       ========================= --}}
    <div class="space-y-4">
        @forelse($porNivelSorted as $nivelNombre => $itemsNivel)
            @php
                $nivelKey = \Illuminate\Support\Str::slug($nivelNombre) . '-' . crc32($nivelNombre);

                $totalPersonasNivel = $itemsNivel->pluck('cabecera.persona_id')->filter()->unique()->count();
                $totalAsignacionesNivel = $itemsNivel->count();

                $first = $itemsNivel->first();
                $nivelId = (int) ($first?->cabecera?->nivel_id ?? 0);

                $isSecundaria = str_contains(mb_strtolower((string) $nivelNombre), 'secund');

                // ✅ si existe el modelo "secundaria" lo usamos
                $secundariaId = (int) ($secundaria?->id ?? 0);
            @endphp

            <div class="rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow overflow-hidden"
                data-nivel-key="{{ $nivelKey }}">

                <!-- Header nivel -->
                <button type="button" @click="toggleLevel('{{ $nivelKey }}')"
                    :aria-expanded="isLevelOpen('{{ $nivelKey }}')"
                    class="w-full text-left p-4 sm:p-5 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-neutral-800/60 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="h-11 w-11 rounded-2xl bg-indigo-50 dark:bg-indigo-900/25 grid place-items-center shrink-0">
                            <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M3 7h18M6 7v14h12V7M9 7V4h6v3" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $nivelNombre }}
                            </p>

                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $totalPersonasNivel }} persona(s)
                                <span class="text-gray-400">· {{ $totalAsignacionesNivel }} asignación(es)</span>
                                @if ($isSecundaria)
                                    · vista por profesor
                                @else
                                    · tabla + drag & drop
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                     bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                            {{ $totalPersonasNivel }}
                        </span>

                        <span class="transition-transform duration-200"
                            :class="isLevelOpen('{{ $nivelKey }}') ? 'rotate-180' : 'rotate-0'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-gray-300"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 15.5l-6-6h12l-6 6z" />
                            </svg>
                        </span>
                    </div>
                </button>

                <!-- Contenido -->
                <div x-show="isLevelOpen('{{ $nivelKey }}')" x-cloak
                    class="border-t border-gray-200 dark:border-neutral-800">

                    {{-- ============ SECUNDARIA ============ --}}
                    @if ($isSecundaria)
                        @php
                            // ✅ usa el nivelId real del collapse actual si está disponible
                            // (normalmente nivelId será el de secundaria en este bloque)
                            $nivelSecId = $nivelId ?: $secundariaId;
                        @endphp

                        <div class="p-4 sm:p-5 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                        bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                                        Cards por profesor
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Arrastra las cards para reordenar profesores · Arrastra filas internas para
                                        reordenar asignaciones
                                    </span>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" @click="openAllProfe()"
                                        class="inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold
                                            border border-emerald-200 dark:border-emerald-800
                                            bg-emerald-50/70 dark:bg-emerald-950/40
                                            text-emerald-700 dark:text-emerald-300
                                            hover:bg-emerald-100 dark:hover:bg-emerald-900/40">
                                        Abrir profesores
                                    </button>
                                    <button type="button" @click="closeAllProfe()"
                                        class="inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold
                                            border border-slate-200 dark:border-slate-800
                                            bg-slate-50/70 dark:bg-slate-950/40
                                            text-slate-700 dark:text-slate-300
                                            hover:bg-slate-100 dark:hover:bg-slate-900/40">
                                        Cerrar profesores
                                    </button>
                                </div>
                            </div>

                            {{-- ✅ CONTENEDOR SORTABLE DE CARDS (usa cabecera_id) --}}
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" data-sortable="personas"
                                data-nivel-id="{{ $nivelSecId }}">

                                @forelse($profesoresSec as $profe)
                                    @php
                                        $pid = (int) ($profe['persona_id'] ?? 0);
                                        $cabId = (int) ($profe['cabecera_id'] ?? 0);

                                        $nombre = $profe['nombre'] ?? 'Sin nombre';
                                        $esp = $profe['especialidad'] ?? null;

                                        $ingSep = $profe['ingreso_sep'] ?? null;
                                        $ingSeg = $profe['ingreso_seg'] ?? null;
                                        $ingCt = $profe['ingreso_ct'] ?? null;

                                        $totalAsig = (int) ($profe['total_asignaciones'] ?? 0);
                                        $totalMat = (int) ($profe['total_materias'] ?? 0);

                                        $materias = $profe['materias'] ?? collect();
                                        $detalles = $profe['detalles'] ?? collect();

                                        // por si viene array
                                        if (is_array($materias)) {
                                            $materias = collect($materias);
                                        }
                                        if (is_array($detalles)) {
                                            $detalles = collect($detalles);
                                        }
                                    @endphp

                                    <div class="persona-card rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow overflow-hidden"
                                        data-id="{{ $cabId }}" wire:key="sec-card-{{ $cabId }}"
                                        data-profe="{{ $cabId }}">
                                        {{-- top bar --}}
                                        <div
                                            class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-blue-600 to-sky-500">
                                        </div>

                                        {{-- header card --}}
                                        <div class="p-4 sm:p-5 flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    {{-- ✅ handle de la card --}}
                                                    <button type="button" data-handle-card
                                                        class="inline-flex items-center justify-center h-9 w-9 rounded-2xl
                                                            border border-gray-200 dark:border-neutral-700
                                                            hover:bg-gray-50 dark:hover:bg-neutral-800/60
                                                            focus:outline-none focus:ring-2 focus:ring-blue-500/20
                                                            cursor-grab active:cursor-grabbing"
                                                        title="Arrastra la card para reordenar profesores">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4 text-gray-500 dark:text-gray-300"
                                                            viewBox="0 0 24 24" fill="currentColor">
                                                            <path
                                                                d="M10 4H6v4h4V4zm8 0h-4v4h4V4zM10 10H6v4h4v-4zm8 0h-4v4h4v-4zM10 16H6v4h4v-4zm8 0h-4v4h4v-4z" />
                                                        </svg>
                                                    </button>

                                                    <div class="min-w-0">
                                                        <p
                                                            class="truncate text-sm font-bold text-gray-900 dark:text-white">
                                                            {{ $nombre }}
                                                        </p>
                                                        @if ($esp)
                                                            <p
                                                                class="truncate text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $esp }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- ✅ fechas debajo del nombre (SEP/SEG/CT) --}}
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold
                                                        bg-slate-50 text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                                                        SEP:
                                                        <span class="ml-1 font-bold">
                                                            @if ($ingSep)
                                                                {{ \Carbon\Carbon::parse($ingSep)->format('d/m/Y') }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold
                                                        bg-slate-50 text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                                                        SEG:
                                                        <span class="ml-1 font-bold">
                                                            @if ($ingSeg)
                                                                {{ \Carbon\Carbon::parse($ingSeg)->format('d/m/Y') }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    </span>
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold
                                                        bg-slate-50 text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                                                        CT:
                                                        <span class="ml-1 font-bold">
                                                            @if ($ingCt)
                                                                {{ \Carbon\Carbon::parse($ingCt)->format('d/m/Y') }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex flex-col items-end gap-2 shrink-0">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                    bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                                                    {{ $totalAsig }} asign.
                                                </span>
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                    bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                                                    {{ $totalMat }} mat.
                                                </span>

                                                <div class="flex gap-3">
                                                    <button type="button"
                                                        class="inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold
                                                border border-amber-200 dark:border-amber-800
                                                bg-amber-50/70 dark:bg-amber-950/40
                                                text-amber-700 dark:text-amber-300
                                                hover:bg-amber-100 dark:hover:bg-amber-900/40"
                                                        @click="$dispatch('abrir-modal-editar-cabecera');
                                                    Livewire.dispatch('editarCabeceraModal', { id: {{ $cabId }} });">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            viewBox="0 0 24 24" fill="currentColor">
                                                            <path
                                                                d="M16.862 3.487a1.5 1.5 0 0 1 2.121 2.121l-10.5 10.5a1.5 1.5 0 0 1-.636.379l-3.5 1a1.5 1.5 0 0 1-1.858-1.858l1-3.5a1.5 1.5 0 0 1 .38-.636l10.493-10.006z" />
                                                        </svg>
                                                        Editar Persona
                                                    </button>


                                                    <button type="button" @click="toggleProfe({{ $cabId }})"
                                                        class="inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-semibold
                                                        border border-gray-200 dark:border-neutral-700
                                                        hover:bg-gray-50 dark:hover:bg-neutral-800/60">
                                                        Detalles
                                                        <span class="transition-transform duration-200"
                                                            :class="isProfeOpen({{ $cabId }}) ? 'rotate-180' :
                                                                'rotate-0'">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                                viewBox="0 0 24 24" fill="currentColor">
                                                                <path d="M12 15.5l-6-6h12l-6 6z" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                </div>


                                            </div>
                                        </div>

                                        {{-- body card --}}
                                        <div x-show="isProfeOpen({{ $cabId }})" x-cloak
                                            class="border-t border-gray-200 dark:border-neutral-800">
                                            {{-- materias chips --}}
                                            <div class="p-4 sm:p-5">
                                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                                    Materias
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @forelse($materias as $m)
                                                        <span
                                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                            bg-gray-100 text-indigo-700 dark:bg-neutral-800 dark:text-gray-200">
                                                            {{ $m }}
                                                        </span>
                                                    @empty
                                                        <span class="text-xs text-gray-400">—</span>
                                                    @endforelse
                                                </div>
                                            </div>

                                            {{-- tabla de asignaciones (sortable dentro del profe) --}}
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead class="bg-gray-50 dark:bg-neutral-800/70">
                                                        <tr class="text-left text-gray-600 dark:text-gray-200">
                                                            <th class="px-4 py-3 font-semibold w-14">Orden</th>
                                                            <th class="px-4 py-3 font-semibold">Materia</th>
                                                            <th class="px-4 py-3 font-semibold">Grado</th>
                                                            <th class="px-4 py-3 font-semibold">Grupo</th>
                                                            <th class="px-4 py-3 font-semibold text-right">Acciones
                                                            </th>
                                                        </tr>
                                                    </thead>

                                                    <tbody wire:ignore.self data-sortable="sec"
                                                        data-nivel-id="{{ $nivelSecId }}"
                                                        data-cabecera-id="{{ $cabId }}"
                                                        class="divide-y divide-gray-100 dark:divide-neutral-800">
                                                        @forelse($detalles as $d)
                                                            @php
                                                                $did = (int) ($d['id'] ?? 0);
                                                            @endphp

                                                            <tr data-id="{{ $did }}"
                                                                wire:key="sec-det-{{ $did }}"
                                                                class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                                                <td class="px-4 py-3">
                                                                    <div class="flex items-center gap-2">
                                                                        <button type="button" data-handle
                                                                            class="inline-flex items-center justify-center h-8 w-8 rounded-xl
                                                                                border border-gray-200 dark:border-neutral-700
                                                                                hover:bg-gray-50 dark:hover:bg-neutral-800/60
                                                                                focus:outline-none focus:ring-2 focus:ring-blue-500/20
                                                                                cursor-grab active:cursor-grabbing"
                                                                            title="Arrastra para reordenar">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                class="h-4 w-4 text-gray-500 dark:text-gray-300"
                                                                                viewBox="0 0 24 24"
                                                                                fill="currentColor">
                                                                                <path
                                                                                    d="M10 4H6v4h4V4zm8 0h-4v4h4V4zM10 10H6v4h4v-4zm8 0h-4v4h4v-4zM10 16H6v4h4v-4zm8 0h-4v4h4v-4z" />
                                                                            </svg>
                                                                        </button>
                                                                        <span
                                                                            class="text-xs font-semibold text-gray-500 dark:text-gray-300">
                                                                            {{ $loop->iteration }}
                                                                        </span>
                                                                    </div>
                                                                </td>

                                                                <td
                                                                    class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                                                    {{ $d['materia'] ?? '—' }}
                                                                </td>

                                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                                    {{ $d['grado'] ?? '—' }}
                                                                </td>

                                                                <td class="px-4 py-3">
                                                                    <span
                                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                                        bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-200">
                                                                        {{ $d['grupo'] ?? '—' }}
                                                                    </span>
                                                                </td>

                                                                <td class="px-4 py-3 text-right">
                                                                    <flux:button variant="primary"
                                                                        class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                                        @click="$dispatch('abrir-modal-editar');
                                                                          Livewire.dispatch('editarModal', { id: {{ $did }} });">
                                                                        <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                                    </flux:button>

                                                                    <flux:button variant="danger"
                                                                        class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white"
                                                                        @click="eliminarAsignacion({{ $did }})">
                                                                        <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                                    </flux:button>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="px-6 py-8 text-center">
                                                                    <p
                                                                        class="text-sm font-semibold text-gray-900 dark:text-white">
                                                                        Sin asignaciones</p>
                                                                    <p
                                                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                                        Este profesor no tiene asignaciones.
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-8 text-center">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Sin profesores
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            No hay asignaciones en secundaria.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @else
                        {{-- ============ OTROS NIVELES (tabla + sortable) ============ --}}
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-neutral-800/70">
                                    <tr class="text-left text-gray-600 dark:text-gray-200">
                                        <th class="px-4 py-3 font-semibold">#</th>
                                        <th class="px-4 py-3 font-semibold">Orden</th>
                                        <th class="px-4 py-3 font-semibold">Personal</th>
                                        <th class="px-4 py-3 font-semibold">Función</th>
                                        <th class="px-4 py-3 font-semibold">Grado</th>
                                        <th class="px-4 py-3 font-semibold">Grupo</th>
                                        <th class="px-4 py-3 font-semibold text-center">SEP</th>
                                        <th class="px-4 py-3 font-semibold text-center">SEG</th>
                                        <th class="px-4 py-3 font-semibold text-center">CT</th>
                                        <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody wire:ignore.self data-sortable="nivel" data-nivel-id="{{ $nivelId }}"
                                    class="divide-y divide-gray-100 dark:divide-neutral-800">
                                    @forelse($itemsNivel as $idx => $row)
                                        @php
                                            $p = $row->cabecera?->persona;
                                            $nombreCompleto = trim(
                                                ($p->nombre ?? '') .
                                                    ' ' .
                                                    ($p->apellido_paterno ?? '') .
                                                    ' ' .
                                                    ($p->apellido_materno ?? ''),
                                            );
                                            $funcion = $row->personaRole?->rolePersona?->nombre;
                                        @endphp

                                        <tr wire:key="pn-{{ $row->id }}" data-id="{{ $row->id }}"
                                            class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $idx + 1 }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <button type="button" data-handle
                                                        class="inline-flex items-center justify-center h-8 w-8 rounded-xl
                                                            border border-gray-200 dark:border-neutral-700
                                                            hover:bg-gray-50 dark:hover:bg-neutral-800/60
                                                            focus:outline-none focus:ring-2 focus:ring-blue-500/20
                                                            cursor-grab active:cursor-grabbing"
                                                        title="Arrastra para reordenar">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4 text-gray-500 dark:text-gray-300"
                                                            viewBox="0 0 24 24" fill="currentColor">
                                                            <path
                                                                d="M10 4H6v4h4V4zm8 0h-4v4h4V4zM10 10H6v4h4v-4zm8 0h-4v4h4v-4zM10 16H6v4h4v-4zm8 0h-4v4h4v-4z" />
                                                        </svg>
                                                    </button>

                                                    {{-- ✅ opcional: muestra el orden real guardado --}}
                                                    <span
                                                        class="text-xs font-semibold text-gray-500 dark:text-gray-300">
                                                        {{ (int) ($row->orden ?? 0) }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $nombreCompleto ?: 'Sin nombre' }}
                                                </div>
                                            </td>

                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                @if ($funcion)
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                        bg-gray-100 text-indigo-700 dark:bg-neutral-800 dark:text-gray-200">
                                                        {{ $funcion }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {{ $row->grado?->nombre ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                                    bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-200">
                                                    {{ $row->grupo?->nombre ?? '—' }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200 text-center">
                                                @if ($row->cabecera->ingreso_sep)
                                                    {{ \Carbon\Carbon::parse($row->cabecera->ingreso_sep)->format('d/m/Y') }}
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200 text-center">
                                                @if ($row->cabecera->ingreso_seg)
                                                    {{ \Carbon\Carbon::parse($row->cabecera->ingreso_seg)->format('d/m/Y') }}
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200 text-center">
                                                @if ($row->cabecera->ingreso_ct)
                                                    {{ \Carbon\Carbon::parse($row->cabecera->ingreso_ct)->format('d/m/Y') }}
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-right space-x-2">
                                                <flux:button variant="primary"
                                                    class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                    @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $row->id }} });">
                                                    <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                </flux:button>

                                                <flux:button variant="danger"
                                                    class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white"
                                                    @click="eliminarAsignacion({{ $row->id }})">
                                                    <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="px-6 py-10 text-center">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Sin
                                                    asignaciones</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    Este nivel no tiene personal asignado.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div
                class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-8 text-center">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Sin resultados</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    No hay personal asignado o tu búsqueda no coincide.
                </p>
            </div>
        @endforelse

        <livewire:persona-nivel.editar-persona-nivel />
        <livewire:persona-nivel.editar-persona-nivel-cabecera />

    </div>

    {{-- ✅ Sortable --}}
    @once
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (function() {

                function getLivewireComponentFrom(el) {
                    const root = el.closest('[wire\\:id]');
                    if (!root) return null;
                    const componentId = root.getAttribute('wire:id');
                    return componentId ? Livewire.find(componentId) : null;
                }

                function initSortableForAll() {
                    if (typeof Sortable === 'undefined') return;

                    // ✅ Niveles (tabla), Secundaria (detalles) y Secundaria (cards)
                    document.querySelectorAll(
                        'tbody[data-sortable="nivel"], tbody[data-sortable="sec"], [data-sortable="personas"]'
                    ).forEach((el) => {
                        if (el._sortable) return;

                        const tipo = el.dataset.sortable;
                        const nivelId = parseInt(el.dataset.nivelId || '0', 10);
                        if (!nivelId) return;

                        const isCards = (tipo === 'personas');

                        el._sortable = new Sortable(el, {
                            animation: 150,
                            handle: isCards ? '[data-handle-card]' : '[data-handle]',
                            draggable: isCards ? '.persona-card' : 'tr[data-id]',
                            dataIdAttr: 'data-id',
                            forceFallback: true,
                            fallbackOnBody: true,
                            fallbackTolerance: 5,

                            onEnd: () => {
                                const ids = el._sortable.toArray().map(v => parseInt(v, 10)).filter(
                                    Boolean);
                                if (!ids.length) return;

                                const component = getLivewireComponentFrom(el);
                                if (!component) return;

                                // ✅ Ordenar PERSONAS (cards) por nivel (persona_nivel.orden)
                                if (tipo === 'personas') {
                                    component.call('ordenarPersonasJs', nivelId, ids);
                                    return;
                                }

                                // ✅ Ordenar DETALLES dentro de un profesor en secundaria
                                if (tipo === 'sec') {
                                    const cabeceraId = parseInt(el.dataset.cabeceraId || '0', 10);
                                    if (!cabeceraId) return;
                                    component.call('ordenarSecJs', nivelId, cabeceraId, ids);
                                    return;
                                }

                                // ✅ Ordenar filas (otros niveles)
                                component.call('ordenarJs', nivelId, ids);
                            },
                        });
                    });
                }

                document.addEventListener('DOMContentLoaded', () => initSortableForAll());

                document.addEventListener('livewire:init', () => {
                    initSortableForAll();
                    Livewire.hook('message.processed', () => initSortableForAll());
                });

                const t = setInterval(() => {
                    if (typeof Sortable !== 'undefined') {
                        clearInterval(t);
                        initSortableForAll();
                    }
                }, 120);
            })
            ();
        </script>
    @endonce
</div>
