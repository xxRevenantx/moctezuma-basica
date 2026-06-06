<div>
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

                <div class="relative"
                    x-data="{ open: false }"
                    @click.away="open = false">
                    <flux:field>
                        <flux:label>Alumno</flux:label>

                        <flux:input
                            wire:model.live.debounce.500ms="query"
                            placeholder="Selecciona el alumno..."
                            @focus="open = true"
                            @input="open = true"
                            wire:keydown.arrow-down.prevent="selectIndexDown"
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

            <div>
                <label class="mb-2 block text-sm font-semibold">
                    Descripción
                </label>

                <div wire:ignore>
                    <textarea id="descripcion_oficio" class="min-h-[260px] w-full">
                        {!! $descripcion_html !!}
                    </textarea>
                </div>

                <flux:error name="descripcion_html" />
            </div>

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
                <flux:button variant="primary" wire:click="guardarOficio" wire:loading.attr="disabled">
                    Guardar cambios
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
                <summary class="cursor-pointer bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700 dark:bg-zinc-800 dark:text-indigo-300">
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
                                        <div class="flex gap-2">
                                            <button type="button"
                                                wire:click="abrirPdfOficio({{ $oficio->id }})"
                                                class="rounded-lg bg-cyan-500 px-3 py-2 text-white hover:bg-cyan-600">
                                                PDF
                                            </button>

                                            <button type="button"
                                                wire:click="eliminarOficio({{ $oficio->id }})"
                                                wire:confirm="¿Deseas eliminar este oficio?"
                                                class="rounded-lg bg-rose-500 px-3 py-2 text-white hover:bg-rose-600">
                                                Eliminar
                                            </button>
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

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const iniciarEditorOficio = () => {
                    if (typeof tinymce === 'undefined') {
                        return;
                    }

                    const editorExistente = tinymce.get('descripcion_oficio');

                    if (editorExistente) {
                        return;
                    }

                    tinymce.init({
                        selector: '#descripcion_oficio',
                        height: 280,
                        menubar: true,
                        plugins: 'lists table link code',
                        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist | table | code',
                        setup: function(editor) {
                            editor.on('change keyup', function() {
                                @this.set('descripcion_html', editor.getContent());
                            });
                        }
                    });
                };

                iniciarEditorOficio();

                Livewire.on('limpiar-editor-oficio', () => {
                    const editor = tinymce.get('descripcion_oficio');

                    if (editor) {
                        editor.setContent('');
                    }
                });

                Livewire.on('abrir-oficio-nueva-ventana', (evento) => {
                    const url = evento.url;

                    if (url) {
                        window.open(url, '_blank');
                    }
                });
            });
        </script>
    @endpush
</div>
