<div>

    {{-- ENCABEZADO --}}
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
            Crear Nuevo Tutor
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Registra una persona responsable. El parentesco se define al relacionarla con cada alumno.
        </p>
    </div>

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle (form-pro) -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-nivel"
            class="group inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                   bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400
                   dark:focus:ring-offset-neutral-900">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/15">
                <!-- ícono lápiz -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M5 19h4l10-10-4-4L5 15v4m14.7-11.3a1 1 0 000-1.4l-2-2a1 1 0 00-1.4 0l-1.6 1.6 3.4 3.4 1.6-1.6z" />
                </svg>
            </span>
            <span class="font-medium">{{ __('Nuevo Tutor') }}</span>
            <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5l-6-6h12l-6 6z" />
                </svg>
            </span>
        </button>

        <!-- Panel (form-pro) -->
        <div id="panel-nivel" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">
            <div class="mx-auto w-full">
                <div
                    class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    {{-- Acento superior --}}
                    <div class="h-1.5 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

                    {{-- Toolbar --}}
                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Nuevo tutor
                            </h2>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                Captura los datos propios de la persona. El parentesco y las autorizaciones no se guardan aquí.
                            </p>
                        </div>


                    </div>

                    {{-- Form --}}
                    <form wire:submit.prevent="guardar" class="p-5 pt-0">
                        {{-- Loader overlay --}}
                        <div wire:loading.flex wire:target="guardar"
                            class="absolute inset-0 z-10 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-950/70">
                            <div
                                class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z">
                                    </path>
                                </svg>
                                <span class="text-sm text-zinc-700 dark:text-zinc-200">Guardando…</span>
                            </div>
                        </div>

                        {{-- Identidad --}}
                        <div
                            class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Identidad</h3>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">La CURP se valida localmente. También se admiten responsables extranjeros o sin CURP.</p>
                                </div>

                                <span
                                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                                    Campos clave
                                </span>
                            </div>

                            {{-- Lector local de INE / CURP --}}
                            <div class="mt-4 overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50 shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/25 dark:via-zinc-950 dark:to-sky-950/20">
                                <div class="flex flex-col gap-3 border-b border-emerald-100 p-4 dark:border-emerald-900/40 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2" />
                                                <rect x="7" y="8" width="10" height="8" rx="1.5" />
                                                <path d="M9.5 11h5M9.5 13.5h3" />
                                            </svg>
                                        </span>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-zinc-900 dark:text-zinc-100">Autollenado desde INE o CURP</h4>
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">Sin tokens</span>
                                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">Procesamiento local</span>
                                            </div>
                                            <p class="mt-1 max-w-3xl text-xs leading-5 text-zinc-600 dark:text-zinc-400">
                                                Sube una fotografía frontal del INE o la constancia de CURP. El sistema presenta una vista previa y nunca modifica el formulario sin tu confirmación.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                                        <span @class([
                                            'rounded-full px-2.5 py-1',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' => $ocr_capacidades['tesseract'] ?? false,
                                            'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' => !($ocr_capacidades['tesseract'] ?? false),
                                        ])>
                                            Tesseract: {{ ($ocr_capacidades['tesseract'] ?? false) ? 'listo' : 'no detectado' }}
                                        </span>
                                        <span @class([
                                            'rounded-full px-2.5 py-1',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' => $ocr_capacidades['pdftoppm'] ?? false,
                                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => !($ocr_capacidades['pdftoppm'] ?? false),
                                        ])>
                                            PDF escaneado: {{ ($ocr_capacidades['pdftoppm'] ?? false) ? 'listo' : 'requiere Poppler' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid gap-4 p-4 lg:grid-cols-[220px_minmax(0,1fr)_auto] lg:items-end">
                                    <flux:field>
                                        <flux:label>Tipo de documento</flux:label>
                                        <flux:select wire:model.live="tipo_documento_ocr">
                                            <option value="ine">INE / identificación</option>
                                            <option value="curp">Constancia de CURP</option>
                                        </flux:select>
                                        <flux:error name="tipo_documento_ocr" />
                                    </flux:field>

                                    <div>
                                        <label for="documento-tutor-ocr" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            Archivo o fotografía
                                        </label>
                                        <input id="documento-tutor-ocr" type="file" wire:model="documento_ocr"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                            capture="environment"
                                            class="block w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-sky-700 hover:file:bg-sky-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-sky-950/50 dark:file:text-sky-300" />
                                        <flux:error name="documento_ocr" />
                                        <p class="mt-1 text-[11px] text-zinc-500">PDF, JPG, PNG o WEBP; máximo {{ round(config('tutor_ocr.max_file_kb', 12288) / 1024, 1) }} MB.</p>
                                    </div>

                                    <flux:button type="button" variant="primary" wire:click="analizarDocumentoTutor"
                                        wire:loading.attr="disabled" wire:target="documento_ocr,analizarDocumentoTutor"
                                        class="rounded-xl bg-emerald-600 hover:bg-emerald-700">
                                        <span wire:loading.remove wire:target="analizarDocumentoTutor">Analizar documento</span>
                                        <span wire:loading wire:target="analizarDocumentoTutor" class="inline-flex items-center gap-2">
                                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                                            </svg>
                                            Leyendo…
                                        </span>
                                    </flux:button>
                                </div>

                                @if (!($ocr_capacidades['tesseract'] ?? false))
                                    <div class="mx-4 mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                        <strong>Lectura parcial disponible:</strong> las constancias de CURP con texto seleccionable funcionan sin instalar nada. Para fotografías del INE o documentos escaneados instala Tesseract y configura <code class="rounded bg-black/5 px-1 py-0.5 dark:bg-white/10">TESSERACT_BINARY</code> en <code>.env</code>.
                                    </div>
                                @endif

                                @if ($ocr_error)
                                    <div class="mx-4 mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                                        <strong>No se pudo completar la lectura:</strong> {{ $ocr_error }}
                                    </div>
                                @endif

                                @if (!empty($ocr_resultado['campos']))
                                    <div class="border-t border-emerald-100 bg-white/75 p-4 dark:border-emerald-900/40 dark:bg-zinc-950/60">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h5 class="font-bold text-zinc-900 dark:text-zinc-100">Vista previa de datos detectados</h5>
                                                <p class="mt-1 text-xs text-zinc-500">
                                                    Método: {{ $ocr_resultado['metodo'] ?? 'Lector local' }}
                                                    @if (isset($ocr_resultado['confianza']) && $ocr_resultado['confianza'] !== null)
                                                        · Confianza OCR: {{ $ocr_resultado['confianza'] }}%
                                                    @endif
                                                </p>
                                            </div>
                                            <label class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                                <input type="checkbox" wire:model="ocr_reemplazar" class="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                                Reemplazar campos que ya tienen información
                                            </label>
                                        </div>

                                        @if (!empty($ocr_resultado['advertencias']))
                                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                                <ul class="space-y-1">
                                                    @foreach ($ocr_resultado['advertencias'] as $advertencia)
                                                        <li class="flex gap-2"><span>•</span><span>{{ $advertencia }}</span></li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                            @foreach (($ocr_resultado['campos'] ?? []) as $campo => $valor)
                                                @if (filled($valor) && isset($etiquetasCamposOcr[$campo]))
                                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 bg-white p-3 transition hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/20">
                                                        <input type="checkbox" value="{{ $campo }}" wire:model="ocr_campos_seleccionados"
                                                            class="mt-0.5 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
                                                        <span class="min-w-0">
                                                            <span class="block text-[11px] font-black uppercase tracking-wide text-zinc-500">{{ $etiquetasCamposOcr[$campo] }}</span>
                                                            <span class="mt-1 block break-words text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                                @if ($campo === 'genero')
                                                                    {{ $valor === 'M' ? 'Masculino' : ($valor === 'F' ? 'Femenino' : $valor) }}
                                                                @else
                                                                    {{ $valor }}
                                                                @endif
                                                            </span>
                                                        </span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>

                                        <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                            <flux:button type="button" variant="outline" wire:click="limpiarResultadoOcr" class="rounded-xl">
                                                Descartar lectura
                                            </flux:button>
                                            <flux:button type="button" variant="primary" wire:click="aplicarDatosOcr" class="rounded-xl bg-emerald-600 hover:bg-emerald-700">
                                                Aplicar al formulario
                                            </flux:button>
                                        </div>

                                        @if (!empty($ocr_resultado['texto']))
                                            <details class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900/70">
                                                <summary class="cursor-pointer text-xs font-bold text-zinc-700 dark:text-zinc-300">Ver texto reconocido para comprobación</summary>
                                                <pre class="mt-3 max-h-48 overflow-auto whitespace-pre-wrap break-words text-[11px] leading-5 text-zinc-600 dark:text-zinc-400">{{ $ocr_resultado['texto'] }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div class="sm:col-span-2 space-y-3">
                                    <label class="inline-flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                                        <input type="checkbox" wire:model.live="sin_curp" class="rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                                        Responsable extranjero o sin CURP disponible
                                    </label>

                                    @if (!$sin_curp)
                                        <flux:field>
                                            <flux:label badge="Requerido">CURP *</flux:label>
                                            <flux:input wire:model.blur="curp" maxlength="18" inputmode="text" autocomplete="off" spellcheck="false"
                                                x-on:input="$el.value = $el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 18)"
                                                class="uppercase tracking-wider"
                                                placeholder="18 caracteres" />
                                            <flux:error name="curp" />
                                            @if ($curp_local_mensaje)
                                                <p class="mt-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $curp_local_mensaje }}</p>
                                            @endif
                                        </flux:field>
                                    @else
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <flux:field>
                                                <flux:label badge="Requerido">Identificador alternativo *</flux:label>
                                                <flux:input wire:model="identificador_alternativo" maxlength="80" class="uppercase" />
                                                <flux:error name="identificador_alternativo" />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label badge="Requerido">Motivo sin CURP *</flux:label>
                                                <flux:input wire:model="motivo_sin_curp" maxlength="255" />
                                                <flux:error name="motivo_sin_curp" />
                                            </flux:field>
                                        </div>
                                    @endif

                                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-semibold text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
                                        El parentesco se captura en la relación con cada alumno, porque una misma persona puede ser madre de un alumno y tía de otro.
                                    </div>
                                </div>

                                <flux:field>
                                    <flux:label badge="Requerido">Género *</flux:label>
                                    <flux:select wire:model="genero">
                                        <option value="">Selecciona…</option>
                                        <option value="M">M - Masculino</option>
                                        <option value="F">F - Femenino</option>
                                        <option value="O">O - Otro</option>
                                    </flux:select>
                                    <flux:error name="genero" />
                                </flux:field>
                            </div>
                        </div>

                        {{-- Secciones colapsables --}}
                        <div class="mt-4 space-y-4" x-data="{ gen: true, dom: false, con: false }"
                            x-on:tutor-ocr-aplicado.window="gen = true; dom = true">
                            {{-- DATOS GENERALES --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
                                    @click="gen=!gen">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos
                                            generales
                                        </h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Nombre completo y
                                            datos de
                                            nacimiento.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-zinc-500 transition" :class="gen ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="gen" x-transition.opacity.duration.200ms class="p-4 pt-0"
                                    style="display:none;">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label badge="Requerido">Nombre *</flux:label>
                                            <flux:input wire:model="nombre" class="uppercase"
                                                placeholder="Ej. CARLOS ALBERTO" />
                                            <flux:error name="nombre" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Apellido paterno *</flux:label>
                                            <flux:input wire:model="apellido_paterno" class="uppercase"
                                                placeholder="Ej. NÚÑEZ" />
                                            <flux:error name="apellido_paterno" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Apellido materno</flux:label>
                                            <flux:input wire:model="apellido_materno" class="uppercase"
                                                placeholder="Ej. PÉREZ (opcional)" />
                                            <flux:error name="apellido_materno" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Fecha de nacimiento</flux:label>
                                            <flux:input type="date" wire:model="fecha_nacimiento"
                                                placeholder="Selecciona una fecha" />
                                            <flux:error name="fecha_nacimiento" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Ciudad nacimiento</flux:label>
                                            <flux:input wire:model="ciudad_nacimiento"
                                                placeholder="Ej. CD ALTAMIRANO" />
                                            <flux:error name="ciudad_nacimiento" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Municipio nacimiento</flux:label>
                                            <flux:input wire:model="municipio_nacimiento"
                                                placeholder="Ej. PUNGARABATO" />
                                            <flux:error name="municipio_nacimiento" />
                                        </flux:field>

                                        <flux:field class="sm:col-span-3">
                                            <flux:label badge="Opcional">Estado nacimiento</flux:label>
                                            <flux:input wire:model="estado_nacimiento" placeholder="Ej. GUERRERO" />
                                            <flux:error name="estado_nacimiento" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>

                            {{-- DOMICILIO --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
                                    @click="dom=!dom">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Domicilio
                                        </h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Calle, colonia,
                                            municipio y
                                            CP.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-zinc-500 transition" :class="dom ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="dom" x-transition.opacity.duration.200ms class="p-4 pt-0"
                                    style="display:none;">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field class="sm:col-span-2">
                                            <flux:label badge="Requerido">Calle *</flux:label>
                                            <flux:input wire:model="calle"
                                                placeholder="Ej. FRANCISCO I. MADERO ORIENTE" />
                                            <flux:error name="calle" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Número</flux:label>
                                            <flux:input wire:model="numero" placeholder="Ej. 800 / S/N" />
                                            <flux:error name="numero" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Colonia *</flux:label>
                                            <flux:input wire:model="colonia" placeholder="Ej. ESQUIPULA" />
                                            <flux:error name="colonia" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Ciudad *</flux:label>
                                            <flux:input wire:model="ciudad" placeholder="Ej. CD ALTAMIRANO" />
                                            <flux:error name="ciudad" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Municipio *</flux:label>
                                            <flux:input wire:model="municipio" placeholder="Ej. PUNGARABATO" />
                                            <flux:error name="municipio" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Estado *</flux:label>
                                            <flux:input wire:model="estado" placeholder="Ej. GUERRERO" />
                                            <flux:error name="estado" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Requerido">Código postal *</flux:label>
                                            <flux:input wire:model="codigo_postal" inputmode="numeric"
                                                placeholder="Ej. 40662" />
                                            <flux:error name="codigo_postal" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>

                            {{-- CONTACTO --}}
                            <div
                                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <button type="button"
                                    class="flex w-full items-center justify-between gap-3 p-4 text-left"
                                    @click="con=!con">
                                    <div>
                                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Contacto
                                        </h3>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Teléfonos y correo
                                            electrónico.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-zinc-500 transition" :class="con ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="con" x-transition.opacity.duration.200ms class="p-4 pt-0"
                                    style="display:none;">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <flux:field>
                                            <flux:label badge="Opcional">Teléfono casa</flux:label>
                                            <flux:input wire:model="telefono_casa" inputmode="tel"
                                                placeholder="Ej. 767 688 0000" />
                                            <flux:error name="telefono_casa" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Teléfono celular</flux:label>
                                            <flux:input wire:model="telefono_celular" inputmode="tel"
                                                placeholder="Ej. 767 123 4567" />
                                            <flux:error name="telefono_celular" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label badge="Opcional">Correo</flux:label>
                                            <flux:input type="email" wire:model="correo_electronico"
                                                placeholder="correo@dominio.com" />
                                            <flux:error name="correo_electronico" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Acciones finales --}}
                        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <flux:button type="button" variant="outline" wire:click="limpiar" class="rounded-xl">
                                Cancelar
                            </flux:button>

                            <flux:button type="submit" variant="primary"
                                class="rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 hover:brightness-110"
                                wire:loading.attr="disabled" wire:target="guardar">
                                <span wire:loading.remove wire:target="guardar">Guardar tutor</span>
                                <span wire:loading wire:target="guardar">Guardando…</span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
