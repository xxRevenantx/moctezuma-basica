<div x-data="{
    open: false,
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
                                                Título
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Clave
                                            </th>

                                            <th
                                                class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-200">
                                                Estado
                                            </th>

                                            <th
                                                class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                                                Acciones
                                            </th>
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
                                                        <flux:badge color="green">
                                                            Activa
                                                        </flux:badge>
                                                    @else
                                                        <flux:badge color="red">
                                                            Inactiva
                                                        </flux:badge>
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
                <flux:field>
                    <flux:label>
                        Tipo de constancia
                    </flux:label>

                    <flux:select wire:model.live="tipo_constancia">
                        @foreach ($plantillasActivas as $plantilla)
                            <flux:select.option value="{{ $plantilla->clave }}">
                                {{ $plantilla->titulo }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- <div wire:key="editor-generar-constancia">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-700 dark:text-zinc-200">
                        {{ $plantilla_titulo ?: 'Plantilla de constancia' }}
                    </h3>

                    <div wire:ignore>
                        <textarea id="editor_constancia">{!! $contenido_html !!}</textarea>
                    </div>

                    <flux:error name="contenido_html" />

                    @if (count($plantilla_variables) > 0)
                        <div
                            class="mt-4 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
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

                    <div class="mt-4">
                        <flux:button type="button" variant="primary"
                            x-on:click="window.sincronizarEditoresConstancia?.()"
                            wire:click="guardarCambiosContenidoActual" wire:loading.attr="disabled"
                            wire:target="guardarCambiosContenidoActual">
                            <span wire:loading.remove wire:target="guardarCambiosContenidoActual">
                                Guardar cambios del contenido actual
                            </span>

                            <span wire:loading wire:target="guardarCambiosContenidoActual">
                                Guardando...
                            </span>
                        </flux:button>
                    </div>
                </div> --}}

                <div class="grid gap-5 md:grid-cols-2">
                    <flux:field>
                        <flux:label>
                            Fecha
                        </flux:label>

                        <flux:input type="date" wire:model.live="fecha_expedicion" />

                        <flux:description>
                            En caso de no asignar una fecha, se asignará la fecha de hoy.
                        </flux:description>

                        <flux:error name="fecha_expedicion" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Dirigido a
                        </flux:label>

                        <flux:input type="text" wire:model.live="dirigido_a" placeholder="A QUIEN CORRESPONDA" />

                        <flux:description>
                            En caso de quedar vacío este campo se dirigirá a A QUIEN CORRESPONDA.
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
                                <flux:label>
                                    Nivel
                                </flux:label>

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
                                    <flux:label>
                                        Grado
                                    </flux:label>

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
                                    <flux:label>
                                        Grupo
                                    </flux:label>

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
                            <flux:label>
                                Alumno
                            </flux:label>

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
                                <p class="font-semibold">
                                    {{ $selectedAlumno['nombre_completo'] }}
                                </p>

                                <p class="text-xs">
                                    {{ $selectedAlumno['matricula'] }}
                                    · {{ $selectedAlumno['nivel'] }}
                                    · {{ $selectedAlumno['grado'] }}
                                    · Grupo {{ $selectedAlumno['grupo'] }}
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
                                        class="cursor-pointer px-4 py-3 text-sm hover:bg-indigo-50 dark:hover:bg-zinc-800
                                            {{ $selectedIndex === $index ? 'bg-indigo-50 dark:bg-zinc-800' : '' }}">
                                        <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                                            {{ $alumno['nombre_completo'] }}
                                        </p>

                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $alumno['matricula'] }}
                                            · {{ $alumno['nivel'] }}
                                            · {{ $alumno['grado'] }}
                                            · Grupo {{ $alumno['grupo'] }}
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
                        x-on:click="
        window.sincronizarEditoresConstancia?.();

        window.ventanaConstancia = window.open('', '_blank');
    "
                        wire:click="descargarConstancia" wire:loading.attr="disabled"
                        wire:target="descargarConstancia">
                        <span wire:loading.remove wire:target="descargarConstancia">
                            {{ $modo_descarga === 'alumno' ? 'Descargar constancia' : 'Descargar constancias ZIP' }}
                        </span>

                        <span wire:loading wire:target="descargarConstancia">
                            Generando...
                        </span>
                    </flux:button>
                </div>
            @endif
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
                            <flux:label>
                                Clave
                            </flux:label>

                            <flux:input type="text" wire:model.live="nueva_clave"
                                placeholder="estudios, conducta, permiso" />

                            <flux:description>
                                Solo minúsculas, números y guion bajo.
                            </flux:description>

                            <flux:error name="nueva_clave" />
                        </flux:field>

                        <flux:field>
                            <flux:label>
                                Título
                            </flux:label>

                            <flux:input type="text" wire:model.live="nuevo_titulo"
                                placeholder="CONSTANCIA DE ESTUDIOS" />

                            <flux:description>
                                El título es el que aparecerá en el encabezado de la constancia.
                            </flux:description>
                            <flux:error name="nuevo_titulo" />
                        </flux:field>
                    </div>

                    <div class="mt-5">
                        <flux:field>
                            <flux:label>
                                Contenido de la plantilla
                            </flux:label>

                            <div wire:ignore>
                                <textarea id="editor_plantilla"></textarea>
                            </div>

                            <flux:error name="nuevo_contenido_html" />
                        </flux:field>
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <flux:field>
                            <flux:label>
                                Variables disponibles
                            </flux:label>

                            <flux:textarea wire:model.live="nuevas_variables" rows="8" />

                            <flux:description>
                                Escribe una variable por línea.
                            </flux:description>

                            <flux:error name="nuevas_variables" />
                        </flux:field>

                        <div class="space-y-4">
                            <div
                                class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                                <p class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                    Variables que puedes usar
                                </p>

                                <div class="grid grid-cols-2 gap-1 text-xs font-mono text-zinc-600 dark:text-zinc-300">
                                    <span>@nombre</span>
                                    <span>@curp</span>
                                    <span>@matricula</span>
                                    <span>@grado</span>
                                    <span>@nivel</span>
                                    <span>@grupo</span>
                                    <span>@generacion</span>
                                    <span>@ciclo</span>
                                    <span>@cct</span>
                                    <span>@sexo</span>
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

                    <flux:button type="button" variant="primary"
                        x-on:click="window.sincronizarEditoresConstancia?.()" wire:click="guardarPlantillaSistema"
                        wire:loading.attr="disabled" wire:target="guardarPlantillaSistema">
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

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                let temporizadorPlantilla = null;
                let temporizadorConstancia = null;

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

                const enviarConDebounce = (propiedadLivewire, contenido, tipo) => {
                    if (tipo === 'plantilla') {
                        clearTimeout(temporizadorPlantilla);

                        temporizadorPlantilla = setTimeout(() => {
                            @this.set(propiedadLivewire, contenido, false);
                        }, 700);

                        return;
                    }

                    clearTimeout(temporizadorConstancia);

                    temporizadorConstancia = setTimeout(() => {
                        @this.set(propiedadLivewire, contenido, false);
                    }, 700);
                };

                const configuracionBase = {
                    menubar: true,
                    branding: false,
                    promotion: false,
                    language: 'es',
                    plugins: 'lists link table code preview fullscreen searchreplace wordcount autoresize',
                    toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist | table link | searchreplace preview fullscreen code',
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

                const iniciarEditor = ({
                    id,
                    propiedadLivewire,
                    contenidoInicial = '',
                    altura = 360,
                    tipo = 'constancia'
                }) => {
                    const elemento = document.getElementById(id);

                    if (!elemento || !window.tinymce) {
                        return;
                    }

                    quitarEditor(id);

                    tinymce.init({
                        ...configuracionBase,
                        selector: `#${id}`,
                        height: altura,

                        setup: function(editor) {
                            editor.on('init', function() {
                                editor.setContent(contenidoInicial ?? '');
                            });

                            editor.on('change undo redo input', function() {
                                enviarConDebounce(propiedadLivewire, editor.getContent(), tipo);
                            });

                            editor.on('blur', function() {
                                @this.set(propiedadLivewire, editor.getContent(), false);
                            });
                        },
                    });
                };

                const iniciarEditorConstancia = (contenido = @js($contenido_html ?? '')) => {
                    esperarTinyMCE(() => {
                        setTimeout(() => {
                            iniciarEditor({
                                id: 'editor_constancia',
                                propiedadLivewire: 'contenido_html',
                                contenidoInicial: contenido,
                                altura: 380,
                                tipo: 'constancia',
                            });
                        }, 150);
                    });
                };

                const iniciarEditorPlantilla = (contenido = '') => {
                    esperarTinyMCE(() => {
                        setTimeout(() => {
                            iniciarEditor({
                                id: 'editor_plantilla',
                                propiedadLivewire: 'nuevo_contenido_html',
                                contenidoInicial: contenido,
                                altura: 420,
                                tipo: 'plantilla',
                            });
                        }, 250);
                    });
                };

                window.sincronizarEditoresConstancia = () => {
                    if (window.tinymce && tinymce.get('editor_plantilla')) {
                        @this.set('nuevo_contenido_html', tinymce.get('editor_plantilla').getContent(), false);
                    }

                    if (window.tinymce && tinymce.get('editor_constancia')) {
                        @this.set('contenido_html', tinymce.get('editor_constancia').getContent(), false);
                    }
                };

                iniciarEditorConstancia();

                window.addEventListener('abrir-modal-plantilla', (event) => {
                    iniciarEditorPlantilla(event.detail.contenido ?? '');
                });

                window.addEventListener('cerrar-modal-plantilla', () => {
                    quitarEditor('editor_plantilla');
                });

                window.addEventListener('actualizar-editor-constancia', (event) => {
                    const contenido = event.detail.contenido ?? '';

                    esperarTinyMCE(() => {
                        if (tinymce.get('editor_constancia')) {
                            tinymce.get('editor_constancia').setContent(contenido);
                        }

                        @this.set('contenido_html', contenido, false);
                    });
                });

                Livewire.hook('morph.updated', () => {
                    if (document.getElementById('editor_constancia') && !tinymce.get('editor_constancia')) {
                        iniciarEditorConstancia(@js($contenido_html ?? ''));
                    }

                    if (document.getElementById('editor_plantilla') && !tinymce.get('editor_plantilla')) {
                        iniciarEditorPlantilla(@js($nuevo_contenido_html ?? ''));
                    }
                });

                document.addEventListener('livewire:navigating', () => {
                    quitarEditor('editor_plantilla');
                    quitarEditor('editor_constancia');
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
            });
        </script>
    @endpush
</div>
