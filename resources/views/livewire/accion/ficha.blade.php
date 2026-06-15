<div class="space-y-6">
    <div
        class="overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div
            class="border-b border-neutral-100 bg-gradient-to-r from-pink-50 via-white to-rose-50 p-5 dark:border-neutral-800 dark:from-pink-500/10 dark:via-neutral-900 dark:to-rose-500/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-pink-600 dark:text-pink-300">
                        Preescolar
                    </p>

                    <h2 class="mt-1 text-xl font-black text-neutral-900 dark:text-white">
                        Ficha descriptiva individual
                    </h2>

                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Captura por campos formativos y recomendaciones. Este módulo reemplaza calificaciones para
                        preescolar.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ([1, 2, 3] as $p)
                        <flux:button type="button" wire:click="cambiarPeriodo({{ $p }})"
                            :variant="$periodo === $p ? 'primary' : 'outline'" size="sm">
                            {{ $this->periodoCorto($p) }}
                        </flux:button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4 p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <flux:field>
                    <flux:label>Ciclo escolar</flux:label>
                    <flux:select wire:model.live="ciclo_escolar_id">
                        @foreach ($this->ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="ciclo_escolar_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Generación</flux:label>
                    <flux:select wire:model.live="generacion_id">
                        <flux:select.option value="">Todas</flux:select.option>

                        @foreach ($this->generaciones as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="generacion_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="grado_id">
                        <flux:select.option value="">Todos</flux:select.option>

                        @foreach ($this->grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">
                                {{ $grado->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grado_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.live="grupo_id">
                        <flux:select.option value="">Todos</flux:select.option>

                        @foreach ($this->grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->asignacionGrupo?->nombre ?? ($grupo->nombre ?? 'Grupo ' . $grupo->id) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grupo_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Filtrar</flux:label>
                    <flux:input type="search" wire:model.live.debounce.350ms="busqueda"
                        placeholder="Alumno, CURP o matrícula" />
                    <flux:error name="busqueda" />
                </flux:field>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="w-full lg:max-w-xl">
                    <flux:field>
                        <flux:label>Fecha y lugar</flux:label>
                        <flux:input type="text" wire:model.live.debounce.500ms="fecha_lugar"
                            class="uppercase font-semibold"
                            placeholder="CD. ALTAMIRANO, GRO., A 11 DE JULIO DEL 2025" />
                        <flux:error name="fecha_lugar" />
                    </flux:field>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button href="{{ $this->urlExcel }}" target="_blank" variant="primary" icon="arrow-down-tray">
                        Excel
                    </flux:button>

                    <flux:button href="{{ $this->urlPdfGrupo }}" target="_blank" variant="filled" icon="document-text">
                        PDF grupo
                    </flux:button>

                    <flux:button type="button" wire:click="descargarPlantillaImportacion" wire:loading.attr="disabled"
                        wire:target="descargarPlantillaImportacion" variant="outline" icon="document-arrow-down">
                        <span wire:loading.remove wire:target="descargarPlantillaImportacion">
                            Plantilla importación
                        </span>
                        <span wire:loading wire:target="descargarPlantillaImportacion">
                            Generando...
                        </span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <div
        class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-500/30 dark:bg-indigo-500/10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-sm font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                    Importar fichas por grado
                </h3>

                <p class="mt-1 text-xs text-neutral-600 dark:text-neutral-400">
                    Primero selecciona ciclo, grado y periodo. Descarga la plantilla, llena los campos formativos y
                    vuelve a subirla.
                </p>

                @if (!$grado_id)
                    <p class="mt-2 text-xs font-semibold text-amber-700">
                        Debes seleccionar un grado antes de descargar o importar.
                    </p>
                @endif
            </div>

            <div class="flex w-full flex-col gap-2 lg:w-auto lg:min-w-[360px]">
                <flux:input type="file" wire:model="archivo_fichas" accept=".xlsx,.xls" />

                <flux:error name="archivo_fichas" />

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" wire:click="importarPlantillaFichas" wire:loading.attr="disabled"
                        wire:target="archivo_fichas,importarPlantillaFichas" variant="primary" icon="arrow-up-tray">
                        <span wire:loading.remove wire:target="archivo_fichas,importarPlantillaFichas">
                            Importar fichas
                        </span>

                        <span wire:loading wire:target="archivo_fichas">
                            Cargando archivo...
                        </span>

                        <span wire:loading wire:target="importarPlantillaFichas">
                            Importando...
                        </span>
                    </flux:button>

                    @if ($archivo_fichas)
                        <flux:button type="button" variant="ghost" wire:click="$set('archivo_fichas', null)">
                            Quitar archivo
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                <thead
                    class="bg-neutral-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-950 dark:text-neutral-400">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Foto</th>
                        <th class="min-w-[240px] px-4 py-3 text-left">Nombre completo</th>
                        <th class="min-w-[170px] px-4 py-3 text-left">CURP</th>

                        @foreach ($campos as $clave => $campoInfo)
                            <th class="min-w-[190px] px-4 py-3 text-center">
                                {{ $campoInfo['label'] }}
                            </th>
                        @endforeach

                        <th class="px-4 py-3 text-center">PDF</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @forelse ($alumnos as $alumno)
                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-white/5">
                            <td class="px-4 py-4 font-bold text-neutral-900 dark:text-white">
                                {{ $alumnos->firstItem() + $loop->index }}
                            </td>

                            <td class="px-4 py-4">
                                @if ($alumno->foto_path)
                                    <img src="{{ Storage::url($alumno->foto_path) }}" alt="Foto"
                                        class="h-11 w-11 rounded-full object-cover ring-2 ring-white shadow">
                                @else
                                    <div
                                        class="grid h-11 w-11 place-items-center rounded-full bg-indigo-100 text-xl shadow-sm dark:bg-indigo-500/15">
                                        👦
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 font-semibold text-neutral-900 dark:text-white">
                                {{ $this->alumnoNombre($alumno) }}

                                <div class="mt-1 text-xs font-normal text-neutral-500">
                                    {{ $alumno->grado?->nombre }} · Grupo
                                    {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 font-mono text-xs text-neutral-700 dark:text-neutral-300">
                                {{ $alumno->curp }}
                            </td>

                            @foreach ($campos as $clave => $campoInfo)
                                @php($completo = filled($fichasResumen[$alumno->id][$clave] ?? null))

                                <td class="px-4 py-4 text-center">
                                    <flux:button type="button"
                                        wire:click="abrirModal({{ $alumno->id }}, '{{ $clave }}')"
                                        :variant="$completo ? 'primary' : 'filled'" size="sm" square
                                        title="Capturar {{ $campoInfo['label'] }}">
                                        @if ($completo)
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2Z" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M5 19h1.4l9.6-9.6L14.6 8 5 17.6V19Zm13.7-10.3-3.4-3.4 1.1-1.1a1.5 1.5 0 0 1 2.1 0l1.3 1.3a1.5 1.5 0 0 1 0 2.1l-1.1 1.1Z" />
                                            </svg>
                                        @endif
                                    </flux:button>
                                </td>
                            @endforeach

                            <td class="px-4 py-4 text-center">
                                <flux:button href="{{ $this->urlPdfAlumno($alumno->id) }}" target="_blank"
                                    variant="filled" size="sm" square title="Descargar ficha PDF">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm-1 1.5L18.5 9H13V3.5ZM8 13h8v2H8v-2Zm0 4h8v2H8v-2Z" />
                                    </svg>
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($campos) }}" class="px-4 py-12 text-center text-neutral-500">
                                No se encontraron alumnos de preescolar con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="flex flex-col gap-3 border-t border-neutral-100 px-5 py-4 text-sm dark:border-neutral-800 md:flex-row md:items-center md:justify-between">
            <p class="font-semibold text-neutral-600 dark:text-neutral-400">
                {{ $alumnos->total() }} registros totales
            </p>

            {{ $alumnos->links() }}
        </div>
    </div>

    @if ($modalAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/70 p-4 backdrop-blur-sm"
            wire:key="modal-ficha">
            <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-neutral-900">
                <div class="flex items-start justify-between gap-4 bg-indigo-600 px-5 py-4 text-white">
                    <h3 class="text-base font-black uppercase leading-6">
                        {{ $campos[$campo]['label'] ?? '' }} |
                        {{ $this->alumnoNombre($this->alumnoModal) }} |
                        {{ $this->periodoNombre() }}
                    </h3>

                    <flux:button type="button" wire:click="cerrarModal" variant="ghost" size="sm" square
                        class="text-white hover:bg-white/15">
                        ✕
                    </flux:button>
                </div>

                <div class="grid gap-5 p-5 lg:grid-cols-[270px_1fr]">
                    @if ($campo !== 'recomendaciones')
                        <div
                            class="rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                            <div class="border-b border-neutral-100 px-5 py-4 text-sm dark:border-neutral-800">
                                Descripción | <strong>{{ $campos[$campo]['label'] ?? '' }}</strong>
                            </div>

                            <div class="space-y-4 px-5 py-5 text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                                <p class="text-justify">
                                    {{ $campos[$campo]['descripcion'] ?? '' }}
                                </p>

                                <div
                                    class="grid min-h-32 place-items-center rounded-xl border border-dashed border-indigo-200 bg-indigo-50 p-4 text-center text-xs font-black uppercase tracking-wide text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                                    <img src="{{ asset($campos[$campo]['imagen'] ?? '') }}"
                                        class="max-h-40 object-contain  " alt="{{ $campos[$campo]['label'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    @endif

                    <div @class(['lg:col-span-2' => $campo === 'recomendaciones'])>
                        <flux:field>
                            <flux:label>
                                {{ $campos[$campo]['label'] ?? 'Descripción' }}
                            </flux:label>

                            <div wire:ignore>
                                <textarea id="editor_ficha_descriptiva"></textarea>
                            </div>

                            <div class="mt-1 flex items-center justify-between text-xs">
                                <flux:description>
                                    {{ mb_strlen($descripcion ?? '') }} caracteres
                                </flux:description>

                                <flux:error name="descripcion" />
                            </div>
                        </flux:field>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:button type="button" x-on:click="window.sincronizarEditorFicha?.()"
                                wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar"
                                variant="primary" icon="check">
                                <span wire:loading.remove wire:target="guardar">Guardar</span>
                                <span wire:loading wire:target="guardar">Guardando...</span>
                            </flux:button>
                            <flux:button type="button" wire:click="cerrarModal" variant="outline">
                                Cancelar
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                let temporizadorFicha = null;

                const esperarTinyMCEFicha = (callback) => {
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
                                'TinyMCE no se pudo cargar. Revisa que el script de TinyMCE esté en el layout.'
                            );
                        }
                    }, 250);
                };

                const quitarEditorFicha = () => {
                    if (window.tinymce && tinymce.get('editor_ficha_descriptiva')) {
                        tinymce.get('editor_ficha_descriptiva').remove();
                    }
                };

                const enviarDescripcionConDebounce = (contenido) => {
                    clearTimeout(temporizadorFicha);

                    temporizadorFicha = setTimeout(() => {
                        @this.set('descripcion', contenido, false);
                    }, 500);
                };

                const iniciarEditorFicha = (contenido = '') => {
                    esperarTinyMCEFicha(() => {
                        setTimeout(() => {
                            const elemento = document.getElementById('editor_ficha_descriptiva');

                            if (!elemento) {
                                return;
                            }

                            quitarEditorFicha();

                            tinymce.init({
                                selector: '#editor_ficha_descriptiva',
                                height: 420,
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
                                setup: function(editor) {
                                    editor.on('init', function() {
                                        editor.setContent(contenido ?? '');
                                    });

                                    editor.on('change undo redo input keyup',
                                        function() {
                                            enviarDescripcionConDebounce(editor
                                                .getContent());
                                        });

                                    editor.on('blur', function() {
                                        @this.set('descripcion', editor
                                            .getContent(), false);
                                    });
                                },
                            });
                        }, 250);
                    });
                };

                window.sincronizarEditorFicha = () => {
                    if (window.tinymce && tinymce.get('editor_ficha_descriptiva')) {
                        @this.set('descripcion', tinymce.get('editor_ficha_descriptiva').getContent(), false);
                    }
                };

                window.addEventListener('abrir-modal-ficha', (event) => {
                    iniciarEditorFicha(event.detail.contenido ?? '');
                });

                window.addEventListener('cerrar-modal-ficha', () => {
                    quitarEditorFicha();
                });

                document.addEventListener('livewire:navigating', () => {
                    quitarEditorFicha();
                });
            });
        </script>
    @endpush
</div>
