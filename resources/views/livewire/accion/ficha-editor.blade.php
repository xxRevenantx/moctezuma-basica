<div
    wire:key="ficha-editor-pro31"
    x-data="fichaEditorPro31"
    x-init="iniciar()"
    data-url-pdf="{{ $urlPdf }}"
    data-url-word="{{ $urlWord }}"
    data-url-regreso="{{ $urlRegreso }}"
    data-solo-lectura="{{ $estadoFicha === 'finalizada' ? '1' : '0' }}"
    id="ficha-editor-pro31" class="ficha-pro31 w-full min-w-0 space-y-5 pb-12" style="display:block; width:100%; min-width:0; max-width:100%; box-sizing:border-box;"
>
    {{-- Diseño compilado con Vite, con refuerzo inline para evitar el colapso visual si el CSS tarda en cargar. --}}

    {{-- Pantalla propia: no se superpone al listado ni se remonta durante los guardados. --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-[#006492] via-[#087eaf] to-[#88ac2e] px-5 py-5 text-white sm:px-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-white/80">Preescolar · Fichas descriptivas PRO 3.1</p>
                    <h1 class="mt-2 text-2xl font-black leading-tight">{{ $this->alumnoNombre($alumno) }}</h1>
                    <p class="mt-1 text-sm text-white/90">
                        {{ $alumno->grado?->nombre ?? 'Preescolar' }} ·
                        {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }} ·
                        {{ $this->periodoNombre() }}
                    </p>
                    <p class="mt-2 text-xs text-white/75">Matrícula: {{ $alumno->matricula }} · Ciclo seleccionado</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/15 px-3 py-2 text-xs font-bold">{{ $estadoFicha === 'finalizada' ? 'Consulta de solo lectura' : 'Edición de borrador' }}</span>
                    <button type="button" x-on:click="regresar()" x-bind:disabled="bloqueado" class="inline-flex items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20 disabled:opacity-50">
                        <flux:icon.arrow-left class="size-4" /> Regresar
                    </button>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 text-xs text-slate-600 dark:border-neutral-800 dark:text-neutral-300">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold">Estado:</span>
                <span @class(['rounded-full px-3 py-1 font-bold', 'bg-emerald-100 text-emerald-700' => $estadoFicha === 'finalizada', 'bg-violet-100 text-violet-700' => $estadoFicha === 'en_revision', 'bg-amber-100 text-amber-700' => $estadoFicha === 'borrador'])>
                    {{ $estadoFicha === 'finalizada' ? 'Finalizada' : ($estadoFicha === 'en_revision' ? 'En revisión' : 'Borrador') }}
                </span>
            </div>
            <span class="flex items-center gap-2 font-bold" aria-live="polite">
                <span x-cloak x-show="estado === 'guardado'" class="text-emerald-700 dark:text-emerald-300">✓ Todos los cambios guardados</span>
                <span x-cloak x-show="estado === 'pendiente'" class="text-amber-700 dark:text-amber-300">● Cambios pendientes</span>
                <span x-cloak x-show="estado === 'guardando'" class="text-sky-700 dark:text-sky-300">Guardando…</span>
                <span x-cloak x-show="estado === 'error'" class="text-rose-700 dark:text-rose-300">No se guardó · revisa el error</span>
            </span>
        </div>
    </section>

    <section class="ficha-pro31-layout" style="display:grid; grid-template-columns:260px minmax(0, 1fr); gap:20px; align-items:start; width:100%; min-width:0; box-sizing:border-box;">
        <aside class="ficha-pro31-aside rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
            <p class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">Campos formativos</p>
            <nav aria-label="Campos de la ficha" class="flex flex-wrap gap-2 lg:flex-col">
                @foreach ($campos as $clave => $informacion)
                    @php $capturado = filled(trim(strip_tags((string) ($resumenCampos[$clave] ?? '')))); @endphp
                    <button
                        type="button"
                        wire:key="editor-campo-{{ $clave }}"
                        data-campo="{{ $clave }}"
                        x-on:click="cambiarCampo($el.dataset.campo)"
                        x-bind:disabled="bloqueado"
                        @class([
                            'flex min-w-0 flex-1 items-center gap-3 rounded-xl px-3 py-3 text-left text-xs font-semibold transition disabled:cursor-wait disabled:opacity-60 lg:w-full lg:flex-none',
                            'bg-[#006492] text-white shadow-sm' => $campo === $clave,
                            'bg-white text-slate-700 hover:bg-sky-50 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800' => $campo !== $clave,
                        ])
                        @if($campo === $clave) aria-current="step" @endif
                    >
                        <span @class(['grid size-7 shrink-0 place-items-center rounded-full font-black', 'bg-lime-100 text-lime-700' => $capturado, 'bg-white/20 text-current' => !$capturado])>{{ $capturado ? '✓' : $loop->iteration }}</span>
                        <span>{{ $informacion['label'] }}</span>
                    </button>
                @endforeach
            </nav>
            <div class="mt-5 rounded-xl border border-slate-200 bg-white p-3 text-xs leading-relaxed text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                <p class="font-black">Guardado seguro</p>
                <p class="mt-1">Se guarda después de dejar de escribir. Al cambiar de apartado o regresar, se confirma el guardado. Si hay un error, el contenido permanece en pantalla.</p>
            </div>
            @if ($this->historialModal->isNotEmpty())
                <div class="mt-5">
                    <h3 class="mb-2 text-xs font-black text-slate-600 dark:text-neutral-300">Últimos movimientos</h3>
                    @foreach ($this->historialModal as $movimiento)
                        <p class="border-b border-slate-200 py-2 text-[11px] text-slate-500 dark:border-neutral-800">{{ ucfirst($movimiento->accion) }} · {{ $movimiento->campo ?? 'Ficha' }} · {{ $movimiento->created_at?->format('d/m H:i') }}</p>
                    @endforeach
                </div>
            @endif
        </aside>

        <div class="ficha-pro31-content min-w-0 space-y-5" style="display:block; width:100%; min-width:0; max-width:100%; box-sizing:border-box;">
            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $campos[$campo]['label'] ?? 'Descripción pedagógica' }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-neutral-400">{{ $campos[$campo]['descripcion'] ?? '' }}</p>
                </div>
                @if($estadoFicha === 'finalizada')
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-200">La ficha está finalizada. Administración debe reabrirla desde el listado para permitir modificaciones.</div>
                @endif
                <div>
                    <label for="editor-ficha-pro31" class="mb-2 block text-sm font-bold text-slate-700 dark:text-neutral-200">Descripción pedagógica</label>
                    {{-- Tiptap gestiona solamente este DOM: Livewire no modifica el contenido del editor. --}}
                    <div wire:ignore class="ficha-pro31-editor-shell" aria-label="Editor pedagógico enriquecido">
                        <textarea x-ref="contenidoInicial" hidden aria-hidden="true">{{ $descripcion }}</textarea>
                        <div class="ficha-pro31-toolbar" role="toolbar" aria-label="Opciones de formato enriquecido">
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('bold')" title="Negrita" aria-label="Negrita"><strong>B</strong></button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('italic')" title="Cursiva" aria-label="Cursiva"><em>I</em></button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('underline')" title="Subrayado" aria-label="Subrayado"><u>U</u></button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('strike')" title="Tachado" aria-label="Tachado"><s>S</s></button>
                            <span class="ficha-pro31-toolbar-divider"></span>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('h2')" title="Título">Título</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('paragraph')" title="Párrafo">Párrafo</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('bullet')" title="Lista con viñetas">• Lista</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('ordered')" title="Lista numerada">1. Lista</button>
                            <span class="ficha-pro31-toolbar-divider"></span>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('left')" title="Alinear a la izquierda">Izq.</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('center')" title="Centrar">Centro</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('right')" title="Alinear a la derecha">Der.</button>
                            <span class="ficha-pro31-toolbar-divider"></span>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('table')" title="Insertar tabla">Tabla</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('addRow')" title="Añadir fila de tabla">+Fila</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('deleteRow')" title="Eliminar fila de tabla">−Fila</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('deleteTable')" title="Eliminar tabla">−Tabla</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('link')" title="Insertar o editar enlace">Enlace</button>
                            <span class="ficha-pro31-toolbar-divider"></span>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('undo')" title="Deshacer">↶</button>
                            <button type="button" x-bind:disabled="soloLectura || bloqueado" x-on:click="formato('redo')" title="Rehacer">↷</button>
                        </div>
                        <div x-ref="editorMount" id="editor-ficha-pro31" class="ficha-pro31-editor-area" aria-label="Descripción pedagógica"></div>
                        <textarea x-ref="editorRespaldo" x-on:input="cambioLocal()" rows="15" class="ficha-pro31-respaldo" hidden aria-label="Descripción de respaldo">{{ $descripcion }}</textarea>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <span>Tiptap 3 · Negritas, listas, tablas, enlaces y alineación · Máximo 5000 caracteres HTML</span>
                        <span x-cloak x-show="editorAlterno" class="font-semibold text-amber-700 dark:text-amber-300">Modo de texto de respaldo — revisa el aviso</span>
                    </div>
                    <flux:error name="descripcion" />
                    <p x-cloak x-show="mensajeError !== ''" x-text="mensajeError" role="alert" class="mt-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200"></p>
                </div>
                @if (!auth()->user()?->isProfessor() && $estadoFicha !== 'finalizada')
                    <details class="rounded-xl border border-emerald-200 bg-emerald-50/40 dark:border-emerald-800 dark:bg-emerald-950/20">
                        <summary class="flex cursor-pointer items-center justify-between p-4 text-sm font-extrabold text-emerald-800 dark:text-emerald-200">
                            <span class="inline-flex items-center gap-2"><flux:icon.sparkles class="size-5" /> Asistente pedagógico IA · Groq</span>
                            <flux:icon.chevron-down class="size-4" />
                        </summary>
                        <div class="space-y-3 border-t border-emerald-200 p-4 dark:border-emerald-800">
                            <p class="text-xs text-slate-600 dark:text-neutral-300">{{ $groq_mensaje }}. Las propuestas se revisan antes de aceptarse; evita compartir datos sensibles.</p>
                            <flux:textarea label="Observaciones o ideas para IA" wire:model="observaciones_ia" rows="3" placeholder="Registra hechos observables y necesidades pedagógicas..." />
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:button type="button" icon="sparkles" variant="primary" x-on:click="generarIA()" x-bind:disabled="bloqueado" :disabled="!($groq_disponible && $groq_modelo_disponible)">Generar sugerencia</flux:button>
                                <flux:button type="button" icon="arrow-path" variant="outline" size="sm" wire:click="verificarGroq">Verificar conexión</flux:button>
                            </div>
                            <flux:error name="observaciones_ia" />
                            @if($propuesta_ia !== '')
                                <div class="grid gap-3 xl:grid-cols-2">
                                    <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs dark:border-neutral-700 dark:bg-neutral-950"><p class="mb-2 font-black">Texto original</p><div class="prose prose-sm max-w-none dark:prose-invert">{!! app(\App\Services\HtmlSanitizerService::class)->sanitize($texto_original_ia) !!}</div></div>
                                    <div class="rounded-xl border border-emerald-200 bg-white p-4 text-xs dark:border-emerald-700 dark:bg-neutral-950"><p class="mb-2 font-black text-emerald-700">Propuesta de IA</p><div class="prose prose-sm max-w-none dark:prose-invert">{!! app(\App\Services\HtmlSanitizerService::class)->sanitize($propuesta_ia) !!}</div></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <flux:button type="button" variant="primary" icon="check" x-on:click="aceptarIA()" x-bind:disabled="bloqueado">Aceptar propuesta</flux:button>
                                    <flux:button type="button" variant="outline" wire:click="descartarSugerenciaIA">Descartar</flux:button>
                                </div>
                            @endif
                        </div>
                    </details>
                @endif
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 dark:border-neutral-800">
                    <div class="text-xs text-slate-500 dark:text-neutral-400"><span x-cloak x-show="!soloLectura">Autoguardado al dejar de escribir</span><span x-cloak x-show="soloLectura">Modo consulta</span></div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button type="button" variant="outline" icon="eye" x-on:click="abrirPDF()" x-bind:disabled="bloqueado">Vista PDF</flux:button>
                        <flux:button type="button" variant="outline" icon="document-text" x-on:click="abrirWord()" x-bind:disabled="bloqueado">Word</flux:button>
                        @if($estadoFicha !== 'finalizada')
                            <flux:button type="button" variant="primary" icon="check" x-on:click="guardarYRegresar()" x-bind:disabled="bloqueado">Guardar y regresar</flux:button>
                        @endif
                        <flux:button type="button" variant="ghost" x-on:click="regresar()" x-bind:disabled="bloqueado">Cerrar editor</flux:button>
                    </div>
                </div>
            </section>
        </div>
    </section>

    {{-- Visor PDF separado del editor: el iframe solo existe si se abre. --}}
    <div x-cloak x-show="vistaPrevia" role="dialog" aria-modal="true" aria-label="Vista previa PDF" class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/80 p-3 backdrop-blur-sm" x-on:keydown.escape.window="vistaPrevia=false">
        <div class="flex h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-neutral-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-neutral-800">
                <h3 class="font-black text-slate-900 dark:text-white">Vista previa de la ficha · PDF</h3>
                <div class="flex gap-2"><a x-bind:href="urlPdf" target="_blank" rel="noopener" class="rounded-lg bg-[#006492] px-4 py-2 text-xs font-bold text-white">Abrir / imprimir</a><flux:button type="button" variant="outline" size="sm" x-on:click="vistaPrevia=false">Cerrar</flux:button></div>
            </div>
            <template x-if="vistaPrevia"><iframe x-bind:src="urlPdf" class="w-full flex-1 bg-white" title="Vista previa PDF"></iframe></template>
        </div>
    </div>
</div>
