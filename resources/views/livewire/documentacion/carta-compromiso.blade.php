<div id="formulario-carta-compromiso"
    x-data="{ configuracion: false, historial: true }"
    x-on:abrir-carta-compromiso.window="window.open($event.detail.url, '_blank', 'noopener')"
    x-on:desplazar-formulario-carta.window="document.getElementById('formulario-carta-compromiso')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
    class="space-y-6">

    <section class="relative overflow-hidden rounded-[30px] border border-slate-200/80 bg-gradient-to-br from-slate-950 via-[#006492] to-[#88AC2E] p-6 text-white shadow-2xl shadow-slate-950/15 sm:p-8">
        <div class="pointer-events-none absolute -right-24 -top-20 size-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-black uppercase tracking-[0.18em] text-white/90">
                    <flux:icon name="document-text" class="size-4" />
                    Documentación institucional
                </div>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Carta compromiso</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-100 sm:text-base">
                    Genera cartas individuales o masivas, conserva el historial y archiva automáticamente el PDF definitivo en el expediente digital del alumno.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:min-w-[360px]">
                <button type="button" wire:click="$set('modo', 'individual')"
                    class="rounded-2xl border px-4 py-4 text-left transition {{ $modo === 'individual' ? 'border-white/50 bg-white/20 ring-2 ring-white/10' : 'border-white/15 bg-white/10 hover:bg-white/15' }}">
                    <p class="text-xs font-bold text-sky-100">Modo</p>
                    <p class="mt-1 text-lg font-black">Individual</p>
                </button>
                <button type="button" wire:click="$set('modo', 'masivo')"
                    class="rounded-2xl border px-4 py-4 text-left transition {{ $modo === 'masivo' ? 'border-white/50 bg-white/20 ring-2 ring-white/10' : 'border-white/15 bg-white/10 hover:bg-white/15' }}">
                    <p class="text-xs font-bold text-lime-100">Modo</p>
                    <p class="mt-1 text-lg font-black">Masivo</p>
                </button>
            </div>
        </div>
    </section>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/25 dark:text-rose-200">
            <p class="font-black">Revisa la información antes de generar la carta.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950/40">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                        <flux:icon name="magnifying-glass" class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-black text-slate-900 dark:text-white">Seleccionar alumno{{ $modo === 'masivo' ? 's' : '' }}</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Busca por nombre, matrícula o CURP y combina los filtros académicos.</p>
                    </div>
                </div>
                @if ($modo === 'masivo')
                    <div class="flex items-center gap-2">
                        <flux:badge color="indigo" rounded>{{ count($seleccionados) }} seleccionado(s)</flux:badge>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="limpiarSeleccionMasiva">Limpiar</flux:button>
                    </div>
                @elseif ($editando_id)
                    <flux:badge color="amber" rounded>Editando {{ $folio }}</flux:badge>
                @endif
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="xl:col-span-4">
                    <flux:field>
                        <flux:label>Buscar alumno</flux:label>
                        <flux:input wire:model.live.debounce.350ms="buscar" type="search" icon="magnifying-glass" clearable
                            placeholder="Nombre, matrícula o CURP..." />
                    </flux:field>
                </div>
                <div class="xl:col-span-2">
                    <flux:field>
                        <flux:label>Ciclo escolar</flux:label>
                        <flux:select wire:model.live="ciclo_escolar_id">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($ciclos as $ciclo)
                                <flux:select.option value="{{ $ciclo['id'] }}">{{ $ciclo['nombre'] }}{{ $ciclo['es_actual'] ? ' · actual' : '' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
                <div class="xl:col-span-2">
                    <flux:field>
                        <flux:label>Nivel</flux:label>
                        <flux:select wire:model.live="nivel_id">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($niveles as $nivel)
                                <flux:select.option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
                <div class="xl:col-span-2">
                    <flux:field>
                        <flux:label>Grado</flux:label>
                        <flux:select wire:model.live="grado_id">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($grados as $grado)
                                @if (!$nivel_id || (int) $grado['nivel_id'] === (int) $nivel_id)
                                    <flux:select.option value="{{ $grado['id'] }}">{{ $grado['nombre'] }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
                <div class="xl:col-span-2">
                    <flux:field>
                        <flux:label>Grupo</flux:label>
                        <flux:select wire:model.live="grupo_id">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($grupos as $grupo)
                                <flux:select.option value="{{ $grupo['id'] }}">{{ $grupo['nombre'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            @if ($modo === 'masivo' && $resultados->isNotEmpty())
                <div class="mt-4 flex justify-end">
                    <flux:button type="button" size="sm" variant="ghost" icon="check-circle" wire:click="seleccionarResultadosVisibles">
                        Seleccionar resultados visibles
                    </flux:button>
                </div>
            @endif

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                @forelse ($resultados as $alumno)
                    <div wire:key="carta-alumno-{{ $alumno->id }}"
                        class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-900 dark:text-white">
                                {{ trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $alumno->matricula }} · {{ $alumno->nivel?->nombre }} · {{ $alumno->grado?->nombre }} · {{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo' }}
                            </p>
                        </div>

                        @if ($modo === 'individual')
                            <flux:button type="button" size="sm"
                                variant="{{ (int) $selectedAlumnoId === (int) $alumno->id ? 'primary' : 'ghost' }}"
                                wire:click="seleccionarAlumno({{ $alumno->id }})">
                                {{ (int) $selectedAlumnoId === (int) $alumno->id ? 'Seleccionado' : 'Seleccionar' }}
                            </flux:button>
                        @else
                            <label class="inline-flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 dark:border-neutral-700 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="seleccionados" value="{{ $alumno->id }}"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Incluir
                            </label>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-500">
                        No se encontraron alumnos con los filtros actuales.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($modo === 'individual' && $selectedAlumno)
        <section class="grid gap-6 2xl:grid-cols-[minmax(0,1.15fr)_minmax(430px,.85fr)]">
            <div class="space-y-6">
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-neutral-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="font-black text-slate-900 dark:text-white">Datos de la carta</h2>
                                <p class="mt-1 text-sm text-slate-500">Los datos se autocompletan y siguen siendo editables antes de generar el PDF.</p>
                            </div>
                            <flux:button type="button" variant="ghost" size="sm" wire:click="nuevo">Nueva</flux:button>
                        </div>
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider text-sky-700 dark:text-sky-300">Alumno seleccionado</p>
                                    <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ $selectedAlumno['nombre_completo'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $selectedAlumno['nivel'] }} · {{ $selectedAlumno['grado'] }} · {{ $selectedAlumno['grupo'] }} · {{ $selectedAlumno['ciclo'] }}
                                    </p>
                                </div>
                                <flux:badge color="blue" rounded>{{ $selectedAlumno['matricula'] }}</flux:badge>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <flux:field>
                                <flux:label>Folio</flux:label>
                                <flux:input wire:model="folio" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Fecha de expedición</flux:label>
                                <flux:input type="date" wire:model="fecha_expedicion" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Lugar</flux:label>
                                <flux:input wire:model="lugar" />
                            </flux:field>
                            <div class="md:col-span-2 xl:col-span-3">
                                <flux:field>
                                    <flux:label>Leyenda oficial del año</flux:label>
                                    <flux:input wire:model="leyenda_anual" placeholder="Ej. 2026, AÑO DE..." />
                                </flux:field>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-5 dark:border-neutral-800">
                            <h3 class="mb-4 font-black text-slate-900 dark:text-white">Responsable y motivo</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <flux:field>
                                    <flux:label>Responsable vinculado</flux:label>
                                    <flux:select wire:model.live="tutor_id">
                                        <flux:select.option value="">Captura manual</flux:select.option>
                                        @foreach ($tutores as $tutor)
                                            <flux:select.option value="{{ $tutor['id'] }}">{{ $tutor['nombre'] }} · {{ $tutor['parentesco'] }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Nombre de quien suscribe</flux:label>
                                    <flux:input wire:model="suscriptor_nombre" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Calidad del responsable</flux:label>
                                    <flux:input wire:model="suscriptor_calidad" placeholder="Ej. mamá del niño" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Referencia al alumno</flux:label>
                                    <flux:input wire:model="referencia_alumno" placeholder="Ej. mi hijo" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Grado solicitado</flux:label>
                                    <flux:input wire:model="grado_texto" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Motivo</flux:label>
                                    <flux:select wire:model.live="motivo_tipo">
                                        <flux:select.option value="economicos">Motivos económicos</flux:select.option>
                                        <flux:select.option value="familiares">Motivos familiares</flux:select.option>
                                        <flux:select.option value="salud">Salud</flux:select.option>
                                        <flux:select.option value="cambio_residencia">Cambio de residencia</flux:select.option>
                                        <flux:select.option value="situacion_academica">Situación académica</flux:select.option>
                                        <flux:select.option value="incorporacion_tardia">Incorporación tardía</flux:select.option>
                                        <flux:select.option value="otro">Otro</flux:select.option>
                                    </flux:select>
                                </flux:field>
                                <div class="md:col-span-2">
                                    <flux:field>
                                        <flux:label>Redacción del motivo</flux:label>
                                        <flux:input wire:model="motivo_texto" placeholder="Escribe el motivo que aparecerá en la carta" />
                                    </flux:field>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                La redacción mantiene exactamente la frase: <strong>“no pudo estudiar el grado anterior”</strong>. No se calcula ni se imprime el número del grado anterior.
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-5 dark:border-neutral-800">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">Texto del compromiso</h3>
                                    <p class="text-sm text-slate-500">Puedes editarlo libremente antes de generar el documento.</p>
                                </div>
                                <flux:button type="button" size="sm" variant="ghost" icon="arrow-path" wire:click="regenerarContenido">Regenerar redacción</flux:button>
                            </div>
                            <div wire:ignore
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                                <textarea id="editor_carta_compromiso">{!! $contenido_cuerpo !!}</textarea>
                            </div>
                            <flux:error name="contenido_cuerpo" />
                        </div>

                        <div class="border-t border-slate-200 pt-5 dark:border-neutral-800">
                            <h3 class="mb-4 font-black text-slate-900 dark:text-white">Destinatario y firmas</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <flux:field>
                                    <flux:label>Destinatario</flux:label>
                                    <flux:input wire:model="destinatario_nombre" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Cargo</flux:label>
                                    <flux:input wire:model="destinatario_cargo" />
                                </flux:field>
                                <div class="md:col-span-2">
                                    <flux:field>
                                        <flux:label>Institución</flux:label>
                                        <flux:input wire:model="destinatario_institucion" />
                                    </flux:field>
                                </div>
                                <flux:field>
                                    <flux:label>Docente</flux:label>
                                    @if (count($docentes))
                                        <flux:select wire:model="docente_nombre">
                                            <flux:select.option value="">Sin nombre</flux:select.option>
                                            @foreach ($docentes as $docente)
                                                <flux:select.option value="{{ $docente['nombre'] }}">{{ $docente['nombre'] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @else
                                        <flux:input wire:model="docente_nombre" placeholder="Nombre del docente" />
                                    @endif
                                </flux:field>
                                <flux:field>
                                    <flux:label>Director(a)</flux:label>
                                    <flux:input wire:model="directora_nombre" />
                                </flux:field>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-5 dark:border-neutral-800">
                            <button type="button" @click="configuracion = !configuracion"
                                class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left dark:border-neutral-700 dark:bg-neutral-950/40">
                                <span>
                                    <span class="block font-black text-slate-900 dark:text-white">Membrete y configuración del nivel</span>
                                    <span class="mt-0.5 block text-sm text-slate-500">Guarda valores predeterminados para futuras cartas del mismo nivel.</span>
                                </span>
                                <flux:icon name="chevron-down" class="size-5 transition" x-bind:class="configuracion ? 'rotate-180' : ''" />
                            </button>

                            <div x-cloak x-show="configuracion" x-collapse class="mt-4 rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Tipo de membrete</flux:label>
                                        <flux:select wire:model="membrete_tipo">
                                            <flux:select.option value="seg">Secretaría de Educación Guerrero</flux:select.option>
                                            <flux:select.option value="cum">Centro Universitario Moctezuma</flux:select.option>
                                        </flux:select>
                                    </flux:field>
                                    <div></div>
                                    <flux:field>
                                        <flux:label>Encabezado — línea 1</flux:label>
                                        <flux:input wire:model="encabezado_linea_1" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Encabezado — línea 2</flux:label>
                                        <flux:input wire:model="encabezado_linea_2" />
                                    </flux:field>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <flux:button type="button" size="sm" variant="primary" wire:click="guardarConfiguracionNivel">Guardar como predeterminado del nivel</flux:button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 dark:border-neutral-800 sm:flex-row sm:justify-end">
                            <flux:button type="button" variant="primary" icon="document-arrow-down"
                                x-on:click="window.sincronizarEditorCartaCompromiso?.()"
                                wire:click="generar" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="generar">{{ $editando_id ? 'Actualizar y abrir PDF' : 'Guardar y abrir PDF' }}</span>
                                <span wire:loading wire:target="generar">Generando…</span>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="2xl:sticky 2xl:top-6 2xl:self-start">
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-slate-100 p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-500">Vista previa</p>
                            <p class="font-black text-slate-900 dark:text-white">Carta vertical</p>
                        </div>
                        <flux:badge rounded color="zinc">PDF carta</flux:badge>
                    </div>

                    <div class="mx-auto aspect-[8.5/11] max-w-[620px] overflow-hidden bg-white p-[5%] text-slate-950 shadow-xl ring-1 ring-slate-300">
                        <div class="flex items-start justify-between gap-4">
                            <div class="w-[46%]">
                                @if ($membrete_tipo === 'seg')
                                    <img src="{{ asset('imagenes/logo-seg.png') }}" alt="SEG" class="h-auto w-full">
                                @else
                                    <img src="{{ asset('imagenes/logo-oficial-cum.png') }}" alt="CUM" class="h-auto max-h-14 w-auto">
                                @endif
                            </div>
                            <div class="flex-1 text-right text-[7px] leading-tight">
                                <p class="font-bold">{{ $encabezado_linea_1 }}</p>
                                <p>{{ $encabezado_linea_2 }}</p>
                            </div>
                        </div>
                        <p class="mt-5 text-right text-[8px] font-black">ASUNTO: {{ $asunto }}</p>
                        <p class="mt-3 text-right text-[8px]">{{ mb_strtoupper($lugar) }}, A {{ $fecha_expedicion ? mb_strtoupper(\Carbon\Carbon::parse($fecha_expedicion)->locale('es')->translatedFormat('j \d\e F \d\e Y')) : '' }}</p>
                        @if ($leyenda_anual)
                            <p class="mt-3 text-right text-[7px] font-bold italic">“{{ $leyenda_anual }}”</p>
                        @endif
                        <div class="mt-5 text-[8px] font-bold uppercase leading-tight">
                            <p>{{ $destinatario_nombre }}</p>
                            <p>{{ $destinatario_cargo }}</p>
                            <p>{{ $destinatario_institucion }}</p>
                        </div>
                        <div
                            class="mt-10 text-justify text-[8px] leading-[1.55] [&_ol]:ml-4 [&_ol]:list-decimal [&_p]:mb-2 [&_ul]:ml-4 [&_ul]:list-disc">
                            {!! app(\App\Services\HtmlSanitizerService::class)->sanitize($contenido_cuerpo) !!}
                        </div>
                        <p class="mt-4 text-[8px]">Sin otro particular reciba un cordial saludo.</p>
                        <div class="mt-5 text-center text-[8px]">
                            <p class="font-bold">ATENTAMENTE</p>
                            <p>{{ $suscriptor_calidad ?: 'Responsable del alumno' }}</p>
                            <div class="mx-auto mt-7 w-36 border-t border-slate-800 pt-1">{{ $suscriptor_nombre }}</div>
                        </div>
                        <div class="mt-6 grid grid-cols-2 gap-8 text-center text-[7px]">
                            <div>
                                <p class="font-bold">Docente</p>
                                <div class="mt-7 border-t border-slate-800 pt-1">{{ $docente_nombre }}</div>
                            </div>
                            <div>
                                <p class="font-bold">Director(a)</p>
                                <div class="mt-7 border-t border-slate-800 pt-1">{{ $directora_nombre }}</div>
                            </div>
                        </div>
                        <img src="{{ asset('imagenes/franja-inferior.png') }}" alt="" class="mt-8 h-auto w-full">
                    </div>
                </div>
            </aside>
        </section>
    @endif

    @if ($modo === 'masivo')
        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-neutral-800">
                <h2 class="font-black text-slate-900 dark:text-white">Configuración para generación masiva</h2>
                <p class="mt-1 text-sm text-slate-500">Cada alumno usará su responsable principal, director, docente, grado y membrete configurado según su nivel.</p>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <flux:field>
                        <flux:label>Fecha de expedición</flux:label>
                        <flux:input type="date" wire:model="fecha_expedicion" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Motivo</flux:label>
                        <flux:select wire:model.live="motivo_tipo">
                            <flux:select.option value="economicos">Motivos económicos</flux:select.option>
                            <flux:select.option value="familiares">Motivos familiares</flux:select.option>
                            <flux:select.option value="salud">Salud</flux:select.option>
                            <flux:select.option value="cambio_residencia">Cambio de residencia</flux:select.option>
                            <flux:select.option value="situacion_academica">Situación académica</flux:select.option>
                            <flux:select.option value="incorporacion_tardia">Incorporación tardía</flux:select.option>
                            <flux:select.option value="otro">Otro</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Redacción del motivo</flux:label>
                        <flux:input wire:model="motivo_texto" />
                    </flux:field>
                </div>

                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                    En todas las cartas masivas se conservará la frase <strong>“no pudo estudiar el grado anterior”</strong>, sin calcular ni mostrar cuál era ese grado.
                </div>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ count($seleccionados) }} alumno(s) seleccionado(s).</p>
                    <flux:button type="button" variant="primary" icon="document-arrow-down" wire:click="generar" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="generar">Generar PDF conjunto</span>
                        <span wire:loading wire:target="generar">Generando cartas…</span>
                    </flux:button>
                </div>
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <button type="button" @click="historial = !historial"
            class="flex w-full items-center justify-between border-b border-slate-200 bg-slate-50/70 px-5 py-4 text-left dark:border-neutral-800 dark:bg-neutral-950/40">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                    <flux:icon name="clock" class="size-5" />
                </span>
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white">Historial de cartas compromiso</h2>
                    <p class="text-sm text-slate-500">Consulta, vuelve a abrir, edita, duplica o cancela cartas emitidas.</p>
                </div>
            </div>
            <flux:icon name="chevron-down" class="size-5 transition" x-bind:class="historial ? 'rotate-180' : ''" />
        </button>

        <div x-show="historial" x-collapse class="p-5 sm:p-6">
            <div class="grid gap-4 md:grid-cols-3">
                <flux:field>
                    <flux:label>Buscar historial</flux:label>
                    <flux:input wire:model.live.debounce.350ms="buscar_historial" type="search" icon="magnifying-glass" clearable placeholder="Folio, alumno o responsable..." />
                </flux:field>
                <flux:field>
                    <flux:label>Estado</flux:label>
                    <flux:select wire:model.live="estado_historial">
                        <flux:select.option value="todos">Todos</flux:select.option>
                        <flux:select.option value="emitida">Emitidas</flux:select.option>
                        <flux:select.option value="cancelada">Canceladas</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Nivel</flux:label>
                    <flux:select wire:model.live="nivel_historial_id">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 dark:border-neutral-800">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wider text-slate-500 dark:bg-neutral-950/50">
                        <tr>
                            <th class="px-4 py-3">Folio / fecha</th>
                            <th class="px-4 py-3">Alumno</th>
                            <th class="px-4 py-3">Responsable</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                        @forelse ($historial as $carta)
                            <tr wire:key="historial-carta-{{ $carta->id }}" class="align-top">
                                <td class="px-4 py-3">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $carta->folio }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $carta->fecha_expedicion?->format('d/m/Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-800 dark:text-slate-100">{{ trim(($carta->alumno?->nombre ?? '') . ' ' . ($carta->alumno?->apellido_paterno ?? '') . ' ' . ($carta->alumno?->apellido_materno ?? '')) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $carta->alumno?->nivel?->nombre }} · {{ $carta->alumno?->grado?->nombre }} · {{ $carta->alumno?->grupo?->asignacionGrupo?->nombre }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $carta->suscriptor_nombre }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge rounded color="{{ $carta->estado_documento === 'cancelada' ? 'red' : 'green' }}">
                                        {{ $carta->estado_documento === 'cancelada' ? 'Cancelada' : 'Emitida' }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($carta->estado_documento !== 'cancelada')
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="abrirPdf({{ $carta->id }})">PDF</flux:button>
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="editarCarta({{ $carta->id }})">Editar</flux:button>
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="duplicarCarta({{ $carta->id }})">Duplicar</flux:button>
                                            <flux:button type="button" size="sm" variant="danger" wire:click="cancelarCarta({{ $carta->id }})" wire:confirm="¿Cancelar esta carta compromiso? El PDF archivado dejará de estar vigente.">Cancelar</flux:button>
                                        @else
                                            <flux:button type="button" size="sm" variant="ghost" wire:click="duplicarCarta({{ $carta->id }})">Duplicar</flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No hay cartas que coincidan con los filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $historial->links() }}</div>
        </div>
    </section>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            const editorId = 'editor_carta_compromiso';
            let temporizador = null;

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

            const quitarEditor = () => {
                if (window.tinymce && tinymce.get(editorId)) {
                    tinymce.get(editorId).remove();
                }
            };

            const sincronizar = (editor, inmediato = false) => {
                const ejecutar = () => {
                    @this.set('contenido_cuerpo', editor.getContent(), false);
                };

                clearTimeout(temporizador);

                if (inmediato) {
                    ejecutar();
                    return;
                }

                temporizador = setTimeout(ejecutar, 300);
            };

            const iniciarEditor = (contenido = '') => {
                esperarTinyMCE(() => {
                    setTimeout(() => {
                        const elemento = document.getElementById(editorId);

                        if (!elemento) {
                            return;
                        }

                        quitarEditor();

                        const oscuro = document.documentElement.classList.contains('dark');

                        tinymce.init({
                            selector: `#${editorId}`,
                            height: 330,
                            menubar: false,
                            branding: false,
                            promotion: false,
                            resize: true,
                            browser_spellcheck: true,
                            contextmenu: false,
                            convert_urls: false,
                            skin: oscuro ? 'oxide-dark' : 'oxide',
                            content_css: oscuro ? 'dark' : 'default',
                            plugins: 'lists code preview fullscreen searchreplace wordcount autoresize',
                            toolbar: 'undo redo | blocks | bold italic underline strikethrough | ' +
                                'alignleft aligncenter alignright alignjustify | bullist numlist | ' +
                                'searchreplace | removeformat | preview fullscreen code',
                            toolbar_mode: 'sliding',
                            valid_elements: 'p[style],br,strong,b,em,i,u,s,strike,span[style],ul,ol,li,blockquote,h1,h2,h3,h4,h5,h6,div[style]',
                            valid_styles: {
                                '*': 'text-align,font-weight,font-style,text-decoration'
                            },
                            content_style: `
                                body {
                                    margin: 0;
                                    padding: 14px 16px;
                                    font-family: Arial, Helvetica, sans-serif;
                                    font-size: 14px;
                                    line-height: 1.65;
                                }

                                p {
                                    margin: 0 0 10px;
                                }

                                ul, ol {
                                    padding-left: 24px;
                                }
                            `,
                            setup: function(editor) {
                                editor.on('init', function() {
                                    editor.setContent(contenido ?? '');
                                });

                                editor.on('change input keyup undo redo', function() {
                                    sincronizar(editor);
                                });

                                editor.on('blur', function() {
                                    sincronizar(editor, true);
                                });
                            },
                        });
                    }, 120);
                });
            };

            window.sincronizarEditorCartaCompromiso = () => {
                const editor = window.tinymce ? tinymce.get(editorId) : null;
                const elemento = document.getElementById(editorId);

                if (editor && elemento && editor.getElement() === elemento) {
                    sincronizar(editor, true);
                }
            };

            window.addEventListener('actualizar-editor-carta-compromiso', (event) => {
                const contenido = event.detail.contenido ?? '';
                const editor = window.tinymce ? tinymce.get(editorId) : null;
                const elemento = document.getElementById(editorId);

                if (!elemento) {
                    return;
                }

                if (editor && editor.getElement() === elemento) {
                    editor.setContent(contenido);
                    return;
                }

                iniciarEditor(contenido);
            });
        });
    </script>
@endpush
