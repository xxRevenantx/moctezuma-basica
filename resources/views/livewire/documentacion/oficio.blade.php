<div
    x-data="{
        visorAbierto: false,
        visorPantallaCompleta: false,
        visorCargando: false,
        visorUrl: '',
        visorWordUrl: '',
        visorBlobUrl: '',
        visorTitulo: 'Vista previa de oficio',
        visorError: '',
        visorSolicitud: 0,

        urlSegura(url) {
            try {
                const destino = new URL(url, window.location.origin);
                if (!['http:', 'https:'].includes(destino.protocol) || destino.origin !== window.location.origin) {
                    return null;
                }
                return destino.href;
            } catch (_) {
                return null;
            }
        },

        async abrirVisorPdf(url, titulo = 'Vista previa de oficio', wordUrl = '') {
            this.cerrarVisorPdf();
            this.visorTitulo = titulo || 'Vista previa de oficio';
            this.visorAbierto = true;
            this.visorCargando = true;

            const destino = this.urlSegura(url);
            const destinoWord = wordUrl ? this.urlSegura(wordUrl) : '';
            if (!destino || (wordUrl && !destinoWord)) {
                this.visorError = 'La dirección del documento no es válida.';
                this.visorCargando = false;
                return;
            }

            this.visorUrl = destino;
            this.visorWordUrl = destinoWord;
            const solicitud = ++this.visorSolicitud;

            try {
                const respuesta = await fetch(destino, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/pdf' }
                });
                if (!respuesta.ok) {
                    throw new Error('El servidor devolvió HTTP ' + respuesta.status + '.');
                }
                if (!respuesta.headers.get('content-type')?.toLowerCase().includes('application/pdf')) {
                    throw new Error('El servidor no devolvió un PDF. Comprueba que tu sesión siga activa.');
                }
                const archivo = await respuesta.blob();
                if ((await archivo.slice(0, 5).text()) !== '%PDF-') {
                    throw new Error('El archivo recibido no es un PDF válido.');
                }
                if (solicitud !== this.visorSolicitud || !this.visorAbierto) return;
                this.visorBlobUrl = URL.createObjectURL(archivo);
                // El indicador se oculta al cargar el iframe, no sólo al recibir la descarga.
            } catch (error) {
                if (solicitud !== this.visorSolicitud || !this.visorAbierto) return;
                this.visorError = error.message || 'No se pudo cargar el oficio.';
                this.visorCargando = false;
            }
        },

        cerrarVisorPdf() {
            this.visorSolicitud++;
            if (this.visorBlobUrl) URL.revokeObjectURL(this.visorBlobUrl);
            this.visorBlobUrl = '';
            this.visorAbierto = false;
            this.visorPantallaCompleta = false;
            this.visorCargando = false;
            this.visorError = '';
            this.visorUrl = '';
            this.visorWordUrl = '';
            document.body.style.overflow = '';
        },

        urlDescargaVisor() {
            if (!this.visorUrl) return '#';
            const destino = new URL(this.visorUrl);
            destino.searchParams.set('download', '1');
            return destino.href;
        }
    }"
    x-on:abrir-visor-oficio.window="abrirVisorPdf($event.detail?.url, $event.detail?.titulo, $event.detail?.wordUrl)"
    x-on:livewire:navigating.window="cerrarVisorPdf()"
>
    @once
        <style>[x-cloak] { display: none !important; }</style>
    @endonce
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="rounded-t-2xl bg-indigo-600 px-5 py-3 text-white">
            <h2 class="text-lg font-semibold tracking-wide">
                OFICIOS DE ALTAS Y BAJAS
            </h2>
        </div>

        <div class="space-y-4 p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <flux:field>
                    <flux:label>No. Oficio</flux:label>
                    <flux:input wire:model="folio" readonly />
                    <flux:error name="folio" />
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de oficio</flux:label>
                    <flux:select wire:model.live="tipo_oficio">
                        <option value="">Selecciona el oficio...</option>
                        <option value="Alta">Alta</option>
                        <option value="Baja">Baja</option>
                    </flux:select>
                    <flux:error name="tipo_oficio" />
                </flux:field>

                <flux:field>
                    <flux:label>Nivel</flux:label>
                    <flux:select wire:model.live="nivel_id">
                        <option value="">Selecciona el nivel...</option>
                        @foreach ($niveles as $nivel)
                            <option value="{{ $nivel['id'] }}">
                                {{ $nivel['nombre'] }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="nivel_id" />
                </flux:field>

                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <flux:field>
                        <flux:label>Alumno</flux:label>

                        <flux:input wire:model.live.debounce.500ms="query" placeholder="Selecciona el alumno..."
                            @focus="open = true" @input="open = true" wire:keydown.arrow-down.prevent="selectIndexDown"
                            wire:keydown.arrow-up.prevent="selectIndexUp"
                            wire:keydown.enter.prevent="selectAlumno({{ $selectedIndex }})" />

                        <flux:error name="selectedAlumno" />
                    </flux:field>

                    @if (count($alumnos) > 0)
                        <ul x-show="open"
                            class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                            @foreach ($alumnos as $index => $alumno)
                                <li wire:click="selectAlumno({{ $index }})"
                                    class="cursor-pointer px-4 py-3 text-sm hover:bg-indigo-50 dark:hover:bg-zinc-700 {{ $selectedIndex === $index ? 'bg-indigo-50 dark:bg-zinc-700' : '' }}">
                                    <div class="font-semibold">
                                        {{ $alumno['nombre_completo'] }}
                                    </div>

                                    <div class="text-xs text-gray-500 dark:text-gray-300">
                                        {{ $alumno['matricula'] }} · {{ $alumno['nivel'] }} ·
                                        {{ $alumno['grado'] }} · Grupo {{ $alumno['grupo'] }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <flux:field>
                    <flux:label>Sección</flux:label>
                    <flux:input wire:model="seccion" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha y lugar</flux:label>
                    <flux:input wire:model="fecha_lugar" />
                </flux:field>

                <flux:field>
                    <flux:label>Asunto</flux:label>
                    <flux:input wire:model="asunto" placeholder="Asunto" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                    <h3 class="mb-3 text-base font-semibold">
                        Dirigido 1:
                    </h3>

                    <div class="space-y-3">
                        <flux:field>
                            <flux:label>Nombre</flux:label>
                            <flux:input wire:model="dirigido_1_nombre" placeholder="Nombre 1" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Cargo</flux:label>
                            <flux:input wire:model="dirigido_1_cargo" placeholder="Cargo 1" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Lugar</flux:label>
                            <flux:input wire:model="dirigido_1_lugar" placeholder="Lugar 1" />
                        </flux:field>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                    <h3 class="mb-3 text-base font-semibold">
                        Dirigido 2:
                    </h3>

                    <div class="space-y-3">
                        <flux:field>
                            <flux:label>Nombre</flux:label>
                            <flux:input wire:model="dirigido_2_nombre" placeholder="Nombre 2" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Cargo</flux:label>
                            <flux:input wire:model="dirigido_2_cargo" placeholder="Cargo 2" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Lugar</flux:label>
                            <flux:input wire:model="dirigido_2_lugar" placeholder="Lugar 2" />
                        </flux:field>
                    </div>
                </div>
            </div>

            {{-- <div>
                <label class="mb-2 block text-sm font-semibold">
                    Descripción
                </label>

                <div wire:ignore>
                    <textarea id="descripcion_oficio" class="min-h-[260px] w-full">
                        {!! $descripcion_html !!}
                    </textarea>
                </div>

                <flux:error name="descripcion_html" />
            </div> --}}

            <flux:field>
                <flux:label>Directora</flux:label>
                <flux:select wire:model="director_id">
                    <option value="">Selecciona la directora...</option>
                    @foreach ($directores as $director)
                        <option value="{{ $director['id'] }}">
                            {{ $director['nombre_completo'] }} - {{ $director['cargo'] }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div>
                <p class="mb-2 text-sm font-semibold">
                    Agregar calificaciones
                </p>

                <div class="flex flex-wrap gap-5 text-sm font-semibold">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model="primer_periodo">
                        1° PERIODO
                    </label>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model="segundo_periodo">
                        2° PERIODO
                    </label>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model="tercer_periodo">
                        3° PERIODO
                    </label>
                </div>
            </div>

            <div class="flex justify-center pt-4">
                <flux:button variant="primary" wire:click="guardarOficio" wire:loading.attr="disabled"
                    wire:target="guardarOficio">
                    <span wire:loading.remove wire:target="guardarOficio">Guardar y visualizar oficio</span>
                    <span wire:loading wire:target="guardarOficio">Guardando oficio...</span>
                </flux:button>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-semibold">
                Oficios generados
            </h3>

            <flux:input wire:model.live.debounce.500ms="buscar_oficio" placeholder="Filtrar..." class="md:w-72" />
        </div>

        @forelse ($oficiosPorNivel as $nombreNivel => $oficios)
            <details class="mb-3 rounded-xl border border-gray-200 dark:border-zinc-700" open>
                <summary
                    class="cursor-pointer bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700 dark:bg-zinc-800 dark:text-indigo-300">
                    {{ mb_strtoupper($nombreNivel) }} | {{ $oficios->count() }}
                </summary>

                <div class="overflow-x-auto p-4">
                    <table class="w-full min-w-[900px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-zinc-800">
                                <th class="px-3 py-3">No. Prog</th>
                                <th class="px-3 py-3">Alumno</th>
                                <th class="px-3 py-3">No. Oficio</th>
                                <th class="px-3 py-3">Tipo de oficio</th>
                                <th class="px-3 py-3">Director</th>
                                <th class="px-3 py-3">Fecha</th>
                                <th class="px-3 py-3">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($oficios as $oficio)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-zinc-800">
                                    <td class="px-3 py-3">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td class="px-3 py-3 font-semibold">
                                        {{ $oficio->alumno?->nombre }}
                                        {{ $oficio->alumno?->apellido_paterno }}
                                        {{ $oficio->alumno?->apellido_materno }}
                                    </td>

                                    <td class="px-3 py-3">
                                        {{ $oficio->folio }}
                                    </td>

                                    <td class="px-3 py-3">
                                        {{ $oficio->tipo_oficio }}
                                    </td>

                                    <td class="px-3 py-3">
                                        {{ trim(($oficio->director?->titulo ?? '') . ' ' . ($oficio->director?->nombre ?? '') . ' ' . ($oficio->director?->apellido_paterno ?? '') . ' ' . ($oficio->director?->apellido_materno ?? '')) }}
                                    </td>

                                    <td class="px-3 py-3">
                                        {{ $oficio->created_at?->format('Y-m-d H:i:s') }}
                                    </td>

                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">

                                            {{-- PDF en visor interno; no se abren ventanas vacías ni se requiere petición Livewire. --}}
                                            <flux:button type="button" size="xs" variant="primary"
                                                data-url="{{ route('misrutas.oficios.pdf', $oficio) }}"
                                                data-word-url="{{ route('misrutas.oficios.word', $oficio) }}"
                                                data-titulo="Oficio · {{ $oficio->folio }}"
                                                x-on:click="abrirVisorPdf($el.dataset.url, $el.dataset.titulo, $el.dataset.wordUrl)">
                                                Ver PDF
                                            </flux:button>

                                            <flux:button size="xs" variant="filled"
                                                :href="route('misrutas.oficios.word', $oficio)"
                                                icon="document-text">Word</flux:button>

                                            {{-- Editar --}}
                                            <flux:button type="button" size="xs" variant="filled"
                                                wire:click="editarOficio({{ $oficio->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="editarOficio({{ $oficio->id }})">
                                                <span wire:loading.remove
                                                    wire:target="editarOficio({{ $oficio->id }})">
                                                    Editar
                                                </span>

                                                <span wire:loading wire:target="editarOficio({{ $oficio->id }})">
                                                    Cargando...
                                                </span>
                                            </flux:button>

                                            {{-- Eliminar --}}
                                            <flux:button type="button" size="xs" variant="danger"
                                                wire:click="eliminarOficio({{ $oficio->id }})"
                                                wire:confirm="¿Seguro que deseas eliminar este oficio?"
                                                wire:loading.attr="disabled"
                                                wire:target="eliminarOficio({{ $oficio->id }})">
                                                <span wire:loading.remove
                                                    wire:target="eliminarOficio({{ $oficio->id }})">
                                                    Eliminar
                                                </span>

                                                <span wire:loading wire:target="eliminarOficio({{ $oficio->id }})">
                                                    Eliminando...
                                                </span>
                                            </flux:button>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
                No hay oficios registrados.
            </div>
        @endforelse
    </div>

@if ($modalEditar)
    <div x-data x-on:keydown.escape.window="$wire.cerrarModalEditar()"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">

        <div class="absolute inset-0" wire:click="cerrarModalEditar">
        </div>

        <div
            class="relative z-10 w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-zinc-900 dark:ring-white/10">

            <div
                class="flex items-center justify-between bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 px-5 py-4 text-white">
                <div>
                    <h3 class="text-lg font-semibold">
                        Editar oficio
                    </h3>

                    <p class="text-sm text-white/80">
                        Modifica la información del oficio seleccionado.
                    </p>
                </div>

                <button type="button" wire:click="cerrarModalEditar"
                    class="rounded-full p-2 text-white hover:bg-white/20">
                    ✕
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto p-5">
                @if ($editarAlumno)
                    <div
                        class="mb-5 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm dark:border-indigo-900 dark:bg-indigo-950/40">
                        <div class="font-semibold text-indigo-700 dark:text-indigo-300">
                            Alumno seleccionado
                        </div>

                        <div class="mt-1 text-gray-700 dark:text-gray-200">
                            {{ $editarAlumno['nombre_completo'] }}
                            —
                            {{ $editarAlumno['matricula'] }}
                            —
                            {{ $editarAlumno['nivel'] }}
                            —
                            {{ $editarAlumno['grado'] }}
                            Grupo {{ $editarAlumno['grupo'] }}
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <flux:field>
                        <flux:label>No. Oficio</flux:label>
                        <flux:input wire:model="editar_folio" />
                        <flux:error name="editar_folio" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tipo de oficio</flux:label>
                        <flux:select wire:model.live="editar_tipo_oficio">
                            <option value="">Selecciona el oficio...</option>
                            <option value="Alta">Alta</option>
                            <option value="Baja">Baja</option>
                        </flux:select>
                        <flux:error name="editar_tipo_oficio" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nivel</flux:label>
                        <flux:select wire:model.live="editar_nivel_id">
                            <option value="">Selecciona el nivel...</option>
                            @foreach ($niveles as $nivel)
                                <option value="{{ $nivel['id'] }}">
                                    {{ $nivel['nombre'] }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="editar_nivel_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Directora</flux:label>
                        <flux:select wire:model="editar_director_id">
                            <option value="">Selecciona la directora...</option>
                            @foreach ($directores as $director)
                                <option value="{{ $director['id'] }}">
                                    {{ $director['nombre_completo'] }}
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="editar_director_id" />
                    </flux:field>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <flux:field>
                        <flux:label>Sección</flux:label>
                        <flux:input wire:model="editar_seccion" />
                        <flux:error name="editar_seccion" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Fecha y lugar</flux:label>
                        <flux:input wire:model="editar_fecha_lugar" />
                        <flux:error name="editar_fecha_lugar" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Asunto</flux:label>
                        <flux:input wire:model="editar_asunto" />
                        <flux:error name="editar_asunto" />
                    </flux:field>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                        <h4 class="mb-3 font-semibold">
                            Dirigido 1
                        </h4>

                        <div class="space-y-3">
                            <flux:field>
                                <flux:label>Nombre</flux:label>
                                <flux:input wire:model="editar_dirigido_1_nombre" />
                                <flux:error name="editar_dirigido_1_nombre" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Cargo</flux:label>
                                <flux:input wire:model="editar_dirigido_1_cargo" />
                                <flux:error name="editar_dirigido_1_cargo" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Lugar</flux:label>
                                <flux:input wire:model="editar_dirigido_1_lugar" />
                                <flux:error name="editar_dirigido_1_lugar" />
                            </flux:field>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                        <h4 class="mb-3 font-semibold">
                            Dirigido 2
                        </h4>

                        <div class="space-y-3">
                            <flux:field>
                                <flux:label>Nombre</flux:label>
                                <flux:input wire:model="editar_dirigido_2_nombre" />
                                <flux:error name="editar_dirigido_2_nombre" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Cargo</flux:label>
                                <flux:input wire:model="editar_dirigido_2_cargo" />
                                <flux:error name="editar_dirigido_2_cargo" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Lugar</flux:label>
                                <flux:input wire:model="editar_dirigido_2_lugar" />
                                <flux:error name="editar_dirigido_2_lugar" />
                            </flux:field>
                        </div>
                    </div>
                </div>
                {{--
                <div class="mt-5">
                    <label class="mb-2 block text-sm font-semibold">
                        Descripción
                    </label>

                    <div wire:ignore>
                        <textarea id="descripcion_oficio_editar" class="min-h-[260px] w-full">
                            {!! $editar_descripcion_html !!}
                        </textarea>
                    </div>

                    <flux:error name="editar_descripcion_html" />
                </div> --}}

                <div class="mt-5">
                    <p class="mb-2 text-sm font-semibold">
                        Agregar calificaciones
                    </p>

                    <div class="flex flex-wrap gap-5 text-sm font-semibold">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="editar_primer_periodo">
                            1° PERIODO
                        </label>

                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="editar_segundo_periodo">
                            2° PERIODO
                        </label>

                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="editar_tercer_periodo">
                            3° PERIODO
                        </label>
                    </div>
                </div>
            </div>

            <div
                class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-950">
                <flux:button type="button" variant="ghost" wire:click="cerrarModalEditar">
                    Cancelar
                </flux:button>

                <flux:button type="button" variant="primary" wire:click="actualizarOficio"
                    wire:loading.attr="disabled">
                    Guardar cambios
                </flux:button>
            </div>
        </div>
    </div>
@endif

{{-- Visor PDF institucional: se teletransporta al body para evitar recortes por contenedores padres. --}}
    <template x-teleport="body">
        <div x-cloak x-show="visorAbierto" x-transition.opacity.duration.200ms
            x-on:keydown.escape.window="if (visorAbierto) cerrarVisorPdf()"
            x-effect="document.body.style.overflow = visorAbierto ? 'hidden' : ''"
            class="fixed inset-0 z-[20000] flex items-center justify-center p-2 sm:p-4"
            role="dialog" aria-modal="true" aria-label="Visor institucional de oficios PDF">
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md"
                x-on:click="cerrarVisorPdf()"></div>

            <section class="relative flex min-h-0 w-full flex-col overflow-hidden border border-white/20 bg-white shadow-[0_25px_90px_rgba(15,23,42,0.45)] dark:border-zinc-700 dark:bg-zinc-900"
                :class="visorPantallaCompleta ? 'h-[100dvh] max-w-none rounded-none' : 'h-[95dvh] max-w-[1500px] rounded-2xl sm:h-[93dvh]'"
                x-on:click.stop>

                <header class="shrink-0 border-b border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="h-1 w-full bg-gradient-to-r from-[#006492] via-indigo-500 to-[#88AC2E]"></div>
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 sm:py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 ring-1 ring-sky-100 dark:bg-sky-950 dark:ring-sky-900">
                                <svg class="h-6 w-6 text-[#006492] dark:text-sky-300" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4 4V20.25H7a2 2 0 0 1-2-2V5.75a2 2 0 0 1 2-2Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3.75v5h5M8.5 13h7M8.5 16.5h7"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-[#006492] dark:text-sky-300">Centro Universitario Moctezuma</p>
                                <h2 class="truncate text-base font-bold text-slate-900 sm:text-lg dark:text-white" x-text="visorTitulo"></h2>
                                <p class="text-xs text-slate-500 dark:text-zinc-400">Visualizador institucional de documentos · PDF</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span x-show="visorBlobUrl && !visorCargando" x-cloak
                                class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 sm:inline-flex dark:bg-emerald-900/30 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Listo para consultar
                            </span>
                            <a x-show="visorUrl && !visorError" x-cloak :href="urlDescargaVisor()"
                                class="inline-flex h-9 items-center gap-2 rounded-lg bg-[#006492] px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-[#004b6e] focus:outline-none focus:ring-2 focus:ring-[#88AC2E] sm:text-sm"
                                aria-label="Descargar oficio PDF">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg>
                                <span class="hidden sm:inline">Descargar PDF</span><span class="sm:hidden">PDF</span>
                            </a>
                            <a x-show="visorWordUrl && !visorError" x-cloak :href="visorWordUrl"
                                class="inline-flex h-9 items-center gap-2 rounded-lg border border-[#006492]/25 bg-sky-50 px-3 text-xs font-semibold text-[#006492] transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-[#88AC2E] dark:bg-sky-950 dark:text-sky-200"
                                aria-label="Descargar oficio Word editable" title="Descargar Word editable (.docx)">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z M14 3v5h5 M9 12l1.5 6 1.5-4 1.5 4 1.5-6"/></svg>
                                <span class="hidden sm:inline">Descargar Word</span><span class="sm:hidden">Word</span>
                            </a>
                            <button type="button" x-on:click="visorPantallaCompleta = !visorPantallaCompleta"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-[#88AC2E] dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                :title="visorPantallaCompleta ? 'Restaurar tamaño' : 'Pantalla completa'"
                                :aria-label="visorPantallaCompleta ? 'Restaurar tamaño' : 'Pantalla completa'">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/></svg>
                            </button>
                            <button type="button" x-on:click="cerrarVisorPdf()" x-ref="cerrarVisor"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-600 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-400 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"
                                title="Cerrar visor" aria-label="Cerrar visor">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </div>
                    </div>
                </header>

                <div class="relative min-h-0 flex-1 bg-slate-100 dark:bg-zinc-950">
                    <template x-if="visorBlobUrl && !visorError">
                        <iframe :src="visorBlobUrl + '#toolbar=1&navpanes=0&view=FitH'"
                            x-on:load="visorCargando = false"
                            x-on:error="visorCargando = false; visorError = 'El navegador no pudo mostrar el PDF.'"
                            class="h-full w-full border-0" title="Vista previa del PDF del oficio"></iframe>
                    </template>
                    <div x-show="visorCargando" x-cloak
                        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 bg-slate-100 dark:bg-zinc-950">
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-zinc-800">
                            <svg class="h-8 w-8 animate-spin text-[#006492]" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".2" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        </div>
                        <div class="text-center"><p class="font-semibold text-slate-800 dark:text-zinc-100">Preparando la vista previa...</p><p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Cargando el PDF de tu oficio institucional.</p></div>
                    </div>
                    <div x-show="visorError" x-cloak
                        class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center">
                        <div class="rounded-full bg-rose-100 p-3 text-rose-600 dark:bg-rose-950/50">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.5 3.5 2.75 17A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.75-3L13.5 3.5a2 2 0 0 0-3 0Z"/></svg>
                        </div>
                        <p class="font-semibold text-slate-800 dark:text-white">No se pudo visualizar el oficio</p>
                        <p class="max-w-md text-sm text-slate-500 dark:text-zinc-400" x-text="visorError"></p>
                        <button type="button" x-on:click="abrirVisorPdf(visorUrl, visorTitulo, visorWordUrl)"
                            class="rounded-lg bg-[#006492] px-4 py-2 text-sm font-medium text-white hover:bg-[#004b6e]">Reintentar</button>
                    </div>
                </div>
                <footer class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-white px-4 py-2.5 text-[11px] text-slate-500 sm:px-6 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <span>Documento de Control Escolar · Uso institucional</span>
                    <span>Usa los controles del PDF para acercar, imprimir o navegar por sus páginas.</span>
                </footer>
            </section>
        </div>
    </template>
</div>
