<div x-data="{
    open: false,
    historialAbierto: false,
}" class="space-y-6">
    @once
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endonce

    <div
        class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        {{-- Encabezado --}}
        <div class="bg-indigo-500 px-6 py-4">
            <h2 class="text-xl font-semibold tracking-wide text-white">
                CONSTANCIAS
            </h2>
        </div>

        {{-- Plantillas del sistema --}}
        <div class="border-b border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div
                class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6M7 4h7l3 3v13H7V4z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-800 dark:text-zinc-100">
                                Plantillas del sistema
                            </h3>

                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Crea, edita y administra las plantillas de constancias.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="hidden rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700 sm:inline-flex">
                            {{ $plantillas->count() }} plantilla(s)
                        </span>

                        <flux:button type="button" variant="primary" wire:click="abrirFormularioPlantilla">
                            Nueva plantilla
                        </flux:button>
                    </div>
                </div>

                <div class="mt-5">
                    @if ($plantillas->count() > 0)
                        <div
                            class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Título</th>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Clave</th>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Estado</th>
                                            <th
                                                class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                                                Acciones</th>
                                        </tr>
                                    </thead>

                                    <tbody
                                        class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                        @foreach ($plantillas as $plantilla)
                                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                                        {{ $plantilla->titulo }}
                                                    </div>

                                                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                                        Actualizada: {{ $plantilla->updated_at?->format('d/m/Y H:i') }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <span
                                                        class="rounded-lg bg-zinc-100 px-2 py-1 font-mono text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                        {{ $plantilla->clave }}
                                                    </span>
                                                </td>

                                                <td class="px-4 py-3">
                                                    @if ($plantilla->activo)
                                                        <flux:badge color="green">Activa</flux:badge>
                                                    @else
                                                        <flux:badge color="red">Inactiva</flux:badge>
                                                    @endif
                                                </td>

                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap justify-end gap-2">
                                                        <flux:button type="button" size="xs" variant="filled"
                                                            wire:click="editarPlantilla({{ $plantilla->id }})">
                                                            Editar
                                                        </flux:button>

                                                        <flux:button type="button" size="xs" variant="ghost"
                                                            wire:click="cambiarEstadoPlantilla({{ $plantilla->id }})">
                                                            {{ $plantilla->activo ? 'Desactivar' : 'Activar' }}
                                                        </flux:button>

                                                        <flux:button type="button" size="xs" variant="danger"
                                                            wire:click="eliminarPlantilla({{ $plantilla->id }})"
                                                            wire:confirm="¿Seguro que deseas eliminar esta plantilla?">
                                                            Eliminar
                                                        </flux:button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                </svg>
                            </div>

                            <p class="mt-3 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                Aún no tienes plantillas registradas.
                            </p>

                            <p class="mt-1 text-xs text-zinc-500">
                                Da clic en “Nueva plantilla” para crear tu primera constancia.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Generación de constancias --}}
        <div class="space-y-6 p-6">
            @if ($plantillasActivas->count() === 0)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Primero crea una plantilla activa para poder generar constancias.
                </div>
            @else
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                                Generar constancia
                            </h3>

                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Selecciona una plantilla activa y el modo de descarga.
                            </p>
                        </div>

                        @if ($plantilla_titulo)
                            <flux:badge color="blue">
                                {{ $plantilla_titulo }}
                            </flux:badge>
                        @endif
                    </div>

                    <flux:field>
                        <flux:label>Tipo de constancia</flux:label>

                        <flux:select wire:model.live="tipo_constancia">
                            @foreach ($plantillasActivas as $plantilla)
                                <flux:select.option value="{{ $plantilla->clave }}">
                                    {{ $plantilla->titulo }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if (count($plantilla_variables) > 0)
                        <div
                            class="mt-4 rounded-xl border border-dashed border-zinc-300 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                Variables disponibles de esta plantilla
                            </p>

                            <ul class="grid gap-1 text-sm sm:grid-cols-2 md:grid-cols-3">
                                @foreach ($plantilla_variables as $variable)
                                    <li class="font-mono text-zinc-600 dark:text-zinc-300">
                                        {{ $variable }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Fecha</flux:label>
                        <flux:input type="date" wire:model.live="fecha_expedicion" />
                        <flux:description>En caso de no asignar una fecha, se asignará la fecha de hoy.
                        </flux:description>
                        <flux:error name="fecha_expedicion" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Dirigido a</flux:label>
                        <flux:input type="text" wire:model.live="dirigido_a" placeholder="A QUIEN CORRESPONDA" />
                        <flux:description>En caso de quedar vacío este campo se dirigirá a A QUIEN CORRESPONDA.
                        </flux:description>
                    </flux:field>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:radio.group wire:model.live="modo_descarga" label="Modo de descarga"
                        class="grid gap-4 md:grid-cols-4">
                        <flux:radio value="alumno" label="Por alumno" />
                        <flux:radio value="nivel" label="Por nivel" />
                        <flux:radio value="grado" label="Por grado" />
                        <flux:radio value="grupo" label="Por grupo" />
                    </flux:radio.group>

                    @if ($modo_descarga !== 'alumno')
                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                            <flux:field>
                                <flux:label>Nivel</flux:label>
                                <flux:select wire:model.live="nivel_id" placeholder="Selecciona nivel">
                                    @foreach ($niveles as $nivel)
                                        <flux:select.option value="{{ $nivel['id'] }}">
                                            {{ $nivel['nombre'] }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="nivel_id" />
                            </flux:field>

                            @if (in_array($modo_descarga, ['grado', 'grupo']))
                                <flux:field>
                                    <flux:label>Grado</flux:label>
                                    <flux:select wire:model.live="grado_id" placeholder="Selecciona grado"
                                        :disabled="!$nivel_id">
                                        @foreach ($grados as $grado)
                                            @if ((int) $grado['nivel_id'] === (int) $nivel_id)
                                                <flux:select.option value="{{ $grado['id'] }}">
                                                    {{ $grado['nombre'] }}
                                                </flux:select.option>
                                            @endif
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="grado_id" />
                                </flux:field>
                            @endif

                            @if ($modo_descarga === 'grupo')
                                <flux:field>
                                    <flux:label>Grupo</flux:label>
                                    <flux:select wire:model.live="grupo_id" placeholder="Selecciona grupo"
                                        :disabled="!$nivel_id">
                                        @foreach ($grupos as $grupo)
                                            @if ((int) $grupo['nivel_id'] === (int) $nivel_id && (!$grado_id || (int) $grupo['grado_id'] === (int) $grado_id))
                                                <flux:select.option value="{{ $grupo['id'] }}">
                                                    {{ $grupo['nombre'] }}
                                                </flux:select.option>
                                            @endif
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="grupo_id" />
                                </flux:field>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($modo_descarga === 'alumno')
                    <div class="relative">
                        <flux:field>
                            <flux:label>Alumno</flux:label>
                            <flux:input type="text" wire:model.live.debounce.500ms="query"
                                wire:keydown.arrow-down.prevent="selectIndexDown"
                                wire:keydown.arrow-up.prevent="selectIndexUp"
                                wire:keydown.enter.prevent="selectAlumno({{ $selectedIndex }})"
                                x-on:focus="open = true" x-on:input="open = true"
                                x-on:blur="setTimeout(() => open = false, 200)"
                                placeholder="Buscar por nombre, matrícula, CURP o folio" />
                            <flux:error name="selectedAlumno" />
                        </flux:field>

                        @if ($selectedAlumno)
                            <div
                                class="mt-3 rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
                                <p class="font-semibold">{{ $selectedAlumno['nombre_completo'] }}</p>
                                <p class="text-xs">
                                    {{ $selectedAlumno['matricula'] }} · {{ $selectedAlumno['nivel'] }} ·
                                    {{ $selectedAlumno['grado'] }} · Grupo {{ $selectedAlumno['grupo'] }}
                                </p>

                                <div class="mt-2">
                                    <flux:button type="button" size="xs" variant="danger"
                                        wire:click="limpiarAlumno">
                                        Limpiar alumno seleccionado
                                    </flux:button>
                                </div>
                            </div>
                        @endif

                        @if (count($alumnos) > 0)
                            <ul x-show="open" x-cloak
                                class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                @foreach ($alumnos as $index => $alumno)
                                    <li wire:click="selectAlumno({{ $index }})"
                                        class="cursor-pointer px-4 py-3 text-sm hover:bg-indigo-50 dark:hover:bg-zinc-800 {{ $selectedIndex === $index ? 'bg-indigo-50 dark:bg-zinc-800' : '' }}">
                                        <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                                            {{ $alumno['nombre_completo'] }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $alumno['matricula'] }} · {{ $alumno['nivel'] }} ·
                                            {{ $alumno['grado'] }} · Grupo {{ $alumno['grupo'] }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <div>
                    <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        Agregar calificaciones
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <flux:checkbox wire:model.live="primer_periodo" label="1° Periodo" />
                        <flux:checkbox wire:model.live="segundo_periodo" label="2° Periodo" />
                        <flux:checkbox wire:model.live="tercer_periodo" label="3° Periodo" />
                    </div>
                </div>

                <div class="flex justify-start">
                    <flux:button type="button" variant="primary"
                        x-on:click="window.ventanaConstancia = window.open('', '_blank');"
                        wire:click="descargarConstancia" wire:loading.attr="disabled"
                        wire:target="descargarConstancia">
                        <span wire:loading.remove wire:target="descargarConstancia">
                            {{ $modo_descarga === 'alumno' ? 'Abrir constancia' : 'Descargar constancias ZIP' }}
                        </span>

                        <span wire:loading wire:target="descargarConstancia">
                            Generando...
                        </span>
                    </flux:button>
                </div>
            @endif
        </div>

        {{-- CRUD de constancias generadas --}}
        <div class="border-t border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div
                class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <button type="button" x-on:click="historialAbierto = !historialAbierto"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-zinc-100 dark:hover:bg-zinc-700/60">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6M7 4h7l3 3v13H7V4z" />
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-800 dark:text-zinc-100">
                                Constancias generadas
                            </h3>

                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Consulta, edita, elimina o vuelve a abrir una constancia generada.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="hidden rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700 sm:inline-flex">
                            {{ $constanciasGeneradas->total() }} registro(s)
                        </span>

                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-500 transition"
                            x-bind:class="historialAbierto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                        </svg>
                    </div>
                </button>

                <div x-show="historialAbierto" x-cloak
                    class="border-t border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                                Historial de constancias
                            </h4>

                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Solo aparecen las constancias individuales guardadas en la base de datos.
                            </p>
                        </div>

                        <div class="w-full sm:w-80">
                            <flux:input type="search" wire:model.live.debounce.500ms="buscar_constancia"
                                placeholder="Buscar folio, alumno o matrícula..." />
                        </div>
                    </div>

                    @if ($constanciasGeneradas->count() > 0)
                        <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Folio</th>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Alumno</th>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Plantilla</th>
                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Fecha</th>
                                            <th
                                                class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                                                Acciones</th>
                                        </tr>
                                    </thead>

                                    <tbody
                                        class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                        @foreach ($constanciasGeneradas as $constanciaGenerada)
                                            @php
                                                $alumnoConstancia = $constanciaGenerada->alumno;
                                                $nombreAlumnoConstancia = trim(
                                                    ($alumnoConstancia->nombre ?? '') .
                                                        ' ' .
                                                        ($alumnoConstancia->apellido_paterno ?? '') .
                                                        ' ' .
                                                        ($alumnoConstancia->apellido_materno ?? ''),
                                                );
                                            @endphp

                                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                                                <td class="px-4 py-3">
                                                    <div
                                                        class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-100">
                                                        {{ $constanciaGenerada->folio }}
                                                    </div>

                                                    <div class="mt-0.5 text-xs text-zinc-500">
                                                        {{ $constanciaGenerada->created_at?->format('d/m/Y H:i') }}
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                                        {{ $nombreAlumnoConstancia ?: 'Sin alumno' }}
                                                    </div>

                                                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $alumnoConstancia?->matricula ?? 'Sin matrícula' }}

                                                        @if ($alumnoConstancia?->nivel?->nombre)
                                                            · {{ $alumnoConstancia->nivel->nombre }}
                                                        @endif

                                                        @if ($alumnoConstancia?->grado?->nombre)
                                                            · {{ $alumnoConstancia->grado->nombre }}
                                                        @endif

                                                        @if ($alumnoConstancia?->grupo?->asignacionGrupo?->nombre)
                                                            · Grupo
                                                            {{ $alumnoConstancia->grupo->asignacionGrupo->nombre }}
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3">
                                                    <div class="text-zinc-700 dark:text-zinc-200">
                                                        {{ $constanciaGenerada->plantilla?->titulo ?? 'Sin plantilla' }}
                                                    </div>

                                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                                        <span>{{ $constanciaGenerada->modo_descarga }}</span>
                                                        @if (($constanciaGenerada->estado_documento ?? 'emitida') === 'cancelada')
                                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Cancelada</span>
                                                        @else
                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Emitida</span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                    {{ $constanciaGenerada->fecha_expedicion?->format('d/m/Y') }}
                                                </td>

                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap justify-end gap-2">
                                                        <flux:button type="button" size="xs" variant="primary"
                                                            wire:click="abrirPdfConstancia({{ $constanciaGenerada->id }})">
                                                            PDF
                                                        </flux:button>

                                                        @if (($constanciaGenerada->estado_documento ?? 'emitida') !== 'cancelada')
                                                            <flux:button type="button" size="xs" variant="filled"
                                                                wire:click="abrirEditarConstancia({{ $constanciaGenerada->id }})">
                                                                Editar
                                                            </flux:button>

                                                            <flux:button type="button" size="xs" variant="danger"
                                                                wire:click="eliminarConstanciaGenerada({{ $constanciaGenerada->id }})"
                                                                wire:confirm="¿Seguro que deseas cancelar esta constancia? El PDF se conservará en el historial.">
                                                                Cancelar
                                                            </flux:button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4">
                            {{ $constanciasGeneradas->links() }}
                        </div>
                    @else
                        <div
                            class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                No hay constancias registradas.
                            </p>

                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Cuando generes una constancia por alumno, aparecerá en este historial.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de crear / editar plantilla --}}
    <div x-data="{
        mostrar: @entangle('mostrar_modal_plantilla'),
    }" x-cloak x-show="mostrar"
        x-on:keydown.escape.window="$wire.cerrarFormularioPlantilla()" class="fixed inset-0 z-[999] overflow-y-auto"
        role="dialog" aria-modal="true">

        <div x-show="mostrar" x-transition.opacity.duration.200ms
            class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm" x-on:click="$wire.cerrarFormularioPlantilla()">
        </div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div x-show="mostrar" x-transition:enter="duration-300 ease-out"
                x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave="duration-200 ease-in"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-900/10 dark:bg-zinc-900 dark:ring-white/10"
                x-on:click.stop>

                <div
                    class="flex items-center justify-between bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            {{ $editando_plantilla ? 'Editar plantilla' : 'Nueva plantilla' }}
                        </h3>

                        <p class="text-sm text-white/80">
                            Las variables deben escribirse una por línea.
                        </p>
                    </div>

                    <button type="button" x-on:click="$wire.cerrarFormularioPlantilla()"
                        class="rounded-xl p-2 text-white/80 transition hover:bg-white/15 hover:text-white"
                        aria-label="Cerrar modal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto p-6">
                    <div class="grid gap-5 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Clave</flux:label>
                            <flux:input type="text" wire:model.live="nueva_clave"
                                placeholder="estudios, conducta, permiso" />
                            <flux:description>Solo minúsculas, números y guion bajo.</flux:description>
                            <flux:error name="nueva_clave" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Título</flux:label>
                            <flux:input type="text" wire:model.live="nuevo_titulo"
                                placeholder="CONSTANCIA DE ESTUDIOS" />
                            <flux:description>El título es el que aparecerá en el encabezado de la constancia.
                            </flux:description>
                            <flux:error name="nuevo_titulo" />
                        </flux:field>
                    </div>

                    <div
                        class="mt-5 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                        <div class="flex flex-col gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex rounded-full bg-violet-600 px-2.5 py-1 text-xs font-semibold text-white">
                                        GroqCloud
                                    </span>

                                    <p class="text-sm font-semibold text-violet-950 dark:text-violet-100">
                                        Asistente de redacción institucional
                                    </p>
                                </div>

                                <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">
                                    Genera una propuesta o mejora el contenido actual. La IA no guarda la plantilla
                                    automáticamente.
                                </p>
                            </div>

                            <flux:field>
                                <flux:label>Instrucción para la IA</flux:label>
                                <flux:textarea wire:model.live.debounce.400ms="instruccion_ia" rows="3"
                                    placeholder="Ejemplo: Redacta una constancia de estudios formal para un trámite de beca. Usa @nombre, @grado, @nivel, @ciclo y @fecha." />
                                <flux:description>
                                    No escribas nombres reales, CURP, matrículas, teléfonos ni otros datos personales.
                                </flux:description>
                                <flux:error name="instruccion_ia" />
                            </flux:field>

                            <div class="flex flex-wrap gap-2">
                                <flux:button type="button" size="sm" variant="primary"
                                    x-on:click="window.redactarPlantillaIA?.('generar')" wire:loading.attr="disabled"
                                    wire:target="redactarPlantillaConIA">
                                    <span wire:loading.remove wire:target="redactarPlantillaConIA">
                                        Generar propuesta
                                    </span>
                                    <span wire:loading wire:target="redactarPlantillaConIA">
                                        Procesando...
                                    </span>
                                </flux:button>

                                <flux:button type="button" size="sm" variant="filled"
                                    x-on:click="window.redactarPlantillaIA?.('mejorar')" wire:loading.attr="disabled"
                                    wire:target="redactarPlantillaConIA">
                                    Mejorar redacción
                                </flux:button>

                                <flux:button type="button" size="sm" variant="ghost"
                                    x-on:click="window.redactarPlantillaIA?.('corregir')" wire:loading.attr="disabled"
                                    wire:target="redactarPlantillaConIA">
                                    Corregir ortografía
                                </flux:button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <flux:field>
                            <flux:label>Contenido de la plantilla</flux:label>
                            <div wire:ignore>
                                <textarea id="editor_plantilla"></textarea>
                            </div>
                            <flux:error name="nuevo_contenido_html" />
                        </flux:field>
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Variables disponibles</flux:label>
                            <flux:textarea wire:model.live="nuevas_variables" rows="8" />
                            <flux:description>Escribe una variable por línea.</flux:description>
                            <flux:error name="nuevas_variables" />
                        </flux:field>

                        <div class="space-y-4">
                            <div
                                class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                <p class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                    Variables que puedes usar
                                </p>

                                <div class="grid grid-cols-2 gap-1 text-xs font-mono text-zinc-600 dark:text-zinc-300">
                                    <span>@sexo</span>
                                    <span>@nombre</span>
                                    <span>@alumno</span>
                                    <span>@curp</span>
                                    <span>@matricula</span>
                                    <span>@grado</span>
                                    <span>@nivel</span>
                                    <span>@grupo</span>
                                    <span>@generacion</span>
                                    <span>@ciclo</span>
                                    <span>@cct</span>
                                    <span>@descripcion</span>
                                    <span>@fecha</span>
                                    <span>@dirigido</span>
                                </div>
                            </div>

                            <flux:checkbox wire:model.live="nuevo_activo" label="Plantilla activa" />
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="cerrarFormularioPlantilla">
                        Cancelar
                    </flux:button>

                    <flux:button type="button" variant="primary" x-on:click="window.sincronizarEditorPlantilla?.()"
                        wire:click="guardarPlantillaSistema" wire:loading.attr="disabled"
                        wire:target="guardarPlantillaSistema">
                        <span wire:loading.remove wire:target="guardarPlantillaSistema">
                            {{ $editando_plantilla ? 'Actualizar plantilla' : 'Guardar plantilla' }}
                        </span>

                        <span wire:loading wire:target="guardarPlantillaSistema">
                            Guardando...
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de editar constancia generada --}}
    <div x-data="{
        mostrar: @entangle('mostrar_modal_editar_constancia'),
    }" x-cloak x-show="mostrar" x-on:keydown.escape.window="$wire.cerrarEditarConstancia()"
        class="fixed inset-0 z-[1000] overflow-y-auto" role="dialog" aria-modal="true">

        <div x-show="mostrar" x-transition.opacity.duration.200ms
            class="fixed inset-0 bg-zinc-950/60 backdrop-blur-sm" x-on:click="$wire.cerrarEditarConstancia()"></div>

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
            <div x-show="mostrar" x-transition:enter="duration-300 ease-out"
                x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave="duration-200 ease-in"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-900/10 dark:bg-zinc-900 dark:ring-white/10"
                x-on:click.stop>

                <div
                    class="flex items-center justify-between bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            Editar constancia
                        </h3>

                        <p class="text-sm text-white/80">
                            Modifica la fecha, dirigido a, periodos o contenido generado.
                        </p>
                    </div>

                    <button type="button" x-on:click="$wire.cerrarEditarConstancia()"
                        class="rounded-xl p-2 text-white/80 transition hover:bg-white/15 hover:text-white"
                        aria-label="Cerrar modal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto p-6">
                    <div class="grid gap-5 md:grid-cols-2">
                        <flux:field>
                            <flux:label>Fecha de expedición</flux:label>
                            <flux:input type="date" wire:model.live="editar_fecha_expedicion" />
                            <flux:error name="editar_fecha_expedicion" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Dirigido a</flux:label>
                            <flux:input type="text" wire:model.live="editar_dirigido_a"
                                placeholder="A QUIEN CORRESPONDA" />
                            <flux:error name="editar_dirigido_a" />
                        </flux:field>
                    </div>

                    <div class="mt-5">
                        <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Periodos de calificaciones
                        </p>

                        <div class="flex flex-wrap gap-4">
                            <flux:checkbox wire:model.live="editar_primer_periodo" label="1° Periodo" />
                            <flux:checkbox wire:model.live="editar_segundo_periodo" label="2° Periodo" />
                            <flux:checkbox wire:model.live="editar_tercer_periodo" label="3° Periodo" />
                        </div>
                    </div>

                    <div class="mt-5">
                        <flux:field>
                            <flux:label>Contenido de la constancia</flux:label>
                            <div wire:ignore>
                                <textarea id="editor_constancia_edicion"></textarea>
                            </div>
                            <flux:error name="editar_contenido_generado_html" />
                        </flux:field>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="cerrarEditarConstancia">
                        Cancelar
                    </flux:button>

                    <flux:button type="button" variant="primary"
                        x-on:click="window.sincronizarEditorConstanciaEdicion?.()" wire:click="actualizarConstancia"
                        wire:loading.attr="disabled" wire:target="actualizarConstancia">
                        <span wire:loading.remove wire:target="actualizarConstancia">
                            Guardar cambios
                        </span>

                        <span wire:loading wire:target="actualizarConstancia">
                            Guardando...
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                let temporizadorPlantilla = null;

                const esperarTinyMCE = (callback) => {
                    if (window.tinymce) {
                        callback();
                        return;
                    }

                    let intentos = 0;

                    const intervalo = setInterval(() => {
                        intentos++;

                        if (window.tinymce) {
                            clearInterval(intervalo);
                            callback();
                        }

                        if (intentos >= 40) {
                            clearInterval(intervalo);
                            console.error(
                                'TinyMCE no se pudo cargar. Revisa la API Key o la conexión a Tiny Cloud.'
                            );
                        }
                    }, 250);
                };

                const quitarEditor = (id) => {
                    if (window.tinymce && tinymce.get(id)) {
                        tinymce.get(id).remove();
                    }
                };

                const enviarPlantillaConDebounce = (contenido) => {
                    clearTimeout(temporizadorPlantilla);

                    temporizadorPlantilla = setTimeout(() => {
                        @this.set('nuevo_contenido_html', contenido, false);
                    }, 700);
                };

                const configuracionBase = {
                    menubar: true,
                    branding: false,
                    promotion: false,
                    language: 'es',
                    plugins: 'lists link table code preview fullscreen searchreplace wordcount autoresize',
                    toolbar: 'undo redo | blocks | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | table link | searchreplace preview fullscreen code',
                    content_style: `
                        body {
                            font-family: Arial, Helvetica, sans-serif;
                            font-size: 14px;
                            line-height: 1.6;
                        }

                        p {
                            margin: 0 0 10px;
                        }
                    `,
                };

                const iniciarEditorPlantilla = (contenido = '') => {
                    esperarTinyMCE(() => {
                        setTimeout(() => {
                            const elemento = document.getElementById('editor_plantilla');

                            if (!elemento) {
                                return;
                            }

                            quitarEditor('editor_plantilla');

                            tinymce.init({
                                ...configuracionBase,
                                selector: '#editor_plantilla',
                                height: 420,
                                setup: function(editor) {
                                    editor.on('init', function() {
                                        editor.setContent(contenido ?? '');
                                    });

                                    editor.on('change undo redo input keyup',
                                        function() {
                                            enviarPlantillaConDebounce(editor
                                                .getContent());
                                        });

                                    editor.on('blur', function() {
                                        @this.set('nuevo_contenido_html', editor
                                            .getContent(), false);
                                    });
                                },
                            });
                        }, 250);
                    });
                };

                const iniciarEditorConstanciaEdicion = (contenido = '') => {
                    esperarTinyMCE(() => {
                        setTimeout(() => {
                            const elemento = document.getElementById('editor_constancia_edicion');

                            if (!elemento) {
                                return;
                            }

                            quitarEditor('editor_constancia_edicion');

                            tinymce.init({
                                ...configuracionBase,
                                selector: '#editor_constancia_edicion',
                                height: 420,
                                setup: function(editor) {
                                    editor.on('init', function() {
                                        editor.setContent(contenido ?? '');
                                    });

                                    editor.on('change undo redo input keyup',
                                        function() {
                                            @this.set(
                                                'editar_contenido_generado_html',
                                                editor.getContent(), false);
                                        });

                                    editor.on('blur', function() {
                                        @this.set(
                                            'editar_contenido_generado_html',
                                            editor.getContent(), false);
                                    });
                                },
                            });
                        }, 250);
                    });
                };

                window.sincronizarEditorPlantilla = () => {
                    if (window.tinymce && tinymce.get('editor_plantilla')) {
                        @this.set('nuevo_contenido_html', tinymce.get('editor_plantilla').getContent(), false);
                    }
                };

                window.redactarPlantillaIA = async (accion) => {
                    const editor = window.tinymce ? tinymce.get('editor_plantilla') : null;

                    if (editor) {
                        await @this.set('nuevo_contenido_html', editor.getContent(), false);
                    }

                    await @this.call('redactarPlantillaConIA', accion);
                };

                window.sincronizarEditorConstanciaEdicion = () => {
                    if (window.tinymce && tinymce.get('editor_constancia_edicion')) {
                        @this.set('editar_contenido_generado_html', tinymce.get('editor_constancia_edicion')
                            .getContent(), false);
                    }
                };

                window.addEventListener('actualizar-editor-plantilla', (event) => {
                    const contenido = event.detail.contenido ?? '';
                    const editor = window.tinymce ? tinymce.get('editor_plantilla') : null;

                    if (editor) {
                        editor.setContent(contenido);
                        return;
                    }

                    iniciarEditorPlantilla(contenido);
                });

                window.addEventListener('abrir-modal-plantilla', (event) => {
                    iniciarEditorPlantilla(event.detail.contenido ?? '');
                });

                window.addEventListener('cerrar-modal-plantilla', () => {
                    quitarEditor('editor_plantilla');
                });

                window.addEventListener('abrir-modal-editar-constancia', (event) => {
                    iniciarEditorConstanciaEdicion(event.detail.contenido ?? '');
                });

                window.addEventListener('cerrar-modal-editar-constancia', () => {
                    quitarEditor('editor_constancia_edicion');
                });

                window.addEventListener('abrir-constancia-nueva-ventana', (event) => {
                    const url = event.detail.url;

                    if (!url) {
                        if (window.ventanaConstancia && !window.ventanaConstancia.closed) {
                            window.ventanaConstancia.close();
                        }

                        return;
                    }

                    if (window.ventanaConstancia && !window.ventanaConstancia.closed) {
                        window.ventanaConstancia.location.href = url;
                        window.ventanaConstancia.focus();
                        return;
                    }

                    window.open(url, '_blank');
                });

                document.addEventListener('livewire:navigating', () => {
                    quitarEditor('editor_plantilla');
                    quitarEditor('editor_constancia_edicion');
                });
            });
        </script>
    @endpush
</div>
