<div class="space-y-6" x-data="{ tab: 'plantel' }">
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#006492] via-indigo-500 to-[#88AC2E]"></div>
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-blue-100/60 blur-3xl dark:bg-blue-900/20"></div>
        <div class="relative flex flex-col gap-6 p-5 pt-7 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-xl shadow-slate-900/15 dark:bg-white dark:text-slate-950">
                    <flux:icon.cog-6-tooth class="h-7 w-7" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#006492]">Media Superior</p>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">Configuración central</span>
                    </div>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">Configuración documental</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Administra datos oficiales, reglas académicas, textos, logos y firmantes con vigencia. Los cambios se aplican a Kardex, Historial académico y Certificados.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="min-w-52 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-4 text-xs font-black">
                        <span class="text-slate-500">Configuración completa</span>
                        <span class="text-[#006492]">{{ $this->avance['porcentaje'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#006492] to-[#88AC2E] transition-all" style="width: {{ $this->avance['porcentaje'] }}%"></div>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-500">{{ $this->avance['firmantes'] }}/3 firmantes configurados</p>
                </div>
                <a href="{{ route('media-superior.documentos.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    <flux:icon.arrow-left class="h-4 w-4" /> Documentos oficiales
                </a>
            </div>
        </div>
    </section>

    <form wire:submit="guardar" class="space-y-6">
        <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="border-b border-slate-200 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/70">
                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['id' => 'plantel', 'label' => 'Plantel y reglas', 'icon' => 'building-office-2'],
                        ['id' => 'logos', 'label' => 'Identidad visual', 'icon' => 'photo'],
                        ['id' => 'textos', 'label' => 'Textos oficiales', 'icon' => 'document-text'],
                        ['id' => 'firmantes', 'label' => 'Firmantes y vigencias', 'icon' => 'users'],
                    ] as $item)
                        <button type="button" @click="tab = '{{ $item['id'] }}'"
                            :class="tab === '{{ $item['id'] }}' ? 'bg-white text-slate-950 shadow-sm ring-1 ring-slate-200 dark:bg-slate-950 dark:text-white dark:ring-slate-700' : 'text-slate-500 hover:bg-white/70 hover:text-slate-800 dark:hover:bg-slate-950/60 dark:hover:text-slate-200'"
                            class="flex items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-black transition">
                            <span :class="tab === '{{ $item['id'] }}' ? 'bg-[#006492] text-white' : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-300'" class="flex h-9 w-9 items-center justify-center rounded-xl transition">
                                <flux:icon :name="$item['icon']" class="h-5 w-5" />
                            </span>
                            {{ $item['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div x-show="tab === 'plantel'" x-cloak class="p-5 sm:p-7">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">Datos oficiales y reglas académicas</h2>
                        <p class="mt-1 text-sm text-slate-500">La dirección, municipio, estado y CCT continúan vinculados a Escuela y Niveles.</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                        <flux:icon.link class="h-4 w-4" /> Datos sincronizados
                    </span>
                </div>

                <div class="grid gap-6 xl:grid-cols-[1.5fr_.8fr]">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <flux:field class="xl:col-span-2"><flux:label>Nombre oficial del plantel</flux:label><flux:input wire:model="nombre_plantel_oficial" placeholder="Vacío = usar Escuela.nombre" /><flux:error name="nombre_plantel_oficial" /></flux:field>
                        <flux:field><flux:label>Localidad de expedición</flux:label><flux:input wire:model="localidad_expedicion" placeholder="Cd. Altamirano, Guerrero" /><flux:error name="localidad_expedicion" /></flux:field>
                        <flux:field><flux:label>Número de acuerdo</flux:label><flux:input wire:model="numero_acuerdo" placeholder="SEG/0031/2021" /><flux:error name="numero_acuerdo" /></flux:field>
                        <flux:field><flux:label>Fecha del acuerdo</flux:label><flux:input type="date" wire:model="fecha_acuerdo" /><flux:error name="fecha_acuerdo" /></flux:field>
                        <flux:field><flux:label>Modalidad</flux:label><flux:input wire:model="modalidad" /><flux:error name="modalidad" /></flux:field>
                        <flux:field><flux:label>Turno</flux:label><flux:input wire:model="turno" /><flux:error name="turno" /></flux:field>
                        <flux:field><flux:label>Calificación mínima</flux:label><flux:input type="number" min="0" max="10" step="0.01" wire:model="calificacion_minima" /><flux:error name="calificacion_minima" /></flux:field>
                        <flux:field><flux:label>Calificación máxima</flux:label><flux:input type="number" min="0" max="10" step="0.01" wire:model="calificacion_maxima" /><flux:error name="calificacion_maxima" /></flux:field>
                        <flux:field><flux:label>Mínima aprobatoria</flux:label><flux:input type="number" min="0" max="10" step="0.01" wire:model="minima_aprobatoria" /><flux:error name="minima_aprobatoria" /></flux:field>
                    </div>

                    <aside class="space-y-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-400">Comportamiento documental</p>
                            <p class="mt-1 text-sm text-slate-500">Valores predeterminados que pueden ajustarse al emitir el Historial académico.</p>
                        </div>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-950">
                            <input type="checkbox" wire:model="mostrar_materias_extra" class="mt-1 rounded border-slate-300 text-[#006492]">
                            <span><span class="block text-sm font-black text-slate-800 dark:text-slate-100">Mostrar materias extra</span><span class="mt-1 block text-xs leading-5 text-slate-500">Siempre separadas y sin intervenir en promedios.</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-950">
                            <input type="checkbox" wire:model="mostrar_foto_historial" class="mt-1 rounded border-slate-300 text-[#006492]">
                            <span><span class="block text-sm font-black text-slate-800 dark:text-slate-100">Fotografía en Historial</span><span class="mt-1 block text-xs leading-5 text-slate-500">Se activa por defecto, pero podrá cambiarse antes de cada emisión.</span></span>
                        </label>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-xs leading-5 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
                            <p class="font-black">Regla protegida</p>
                            <p class="mt-1">Las calificaciones finales se imprimen como enteros truncados y nunca se redondean.</p>
                        </div>
                    </aside>
                </div>
            </div>

            <div x-show="tab === 'logos'" x-cloak class="p-5 sm:p-7">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">Identidad visual de los documentos</h2>
                    <p class="mt-1 text-sm text-slate-500">Comprueba visualmente los recursos antes de guardar las rutas.</p>
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ([
                        ['label' => 'Logotipo SEG', 'model' => 'logo_seg_path', 'path' => $logo_seg_path, 'help' => 'Se utiliza en el extremo izquierdo de los formatos oficiales.'],
                        ['label' => 'Logotipo del plantel', 'model' => 'logo_plantel_path', 'path' => $logo_plantel_path, 'help' => 'Se utiliza en encabezados, Kardex, Historial y Certificados.'],
                    ] as $logo)
                        <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 dark:border-slate-800">
                            <div class="flex min-h-48 items-center justify-center bg-[radial-gradient(circle_at_center,_#f8fafc,_#e2e8f0)] p-8 dark:bg-slate-900">
                                <img src="{{ asset(ltrim($logo['path'], '/')) }}" alt="{{ $logo['label'] }}" class="max-h-28 max-w-[80%] object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                <div class="hidden h-24 w-24 items-center justify-center rounded-2xl border border-dashed border-slate-300 text-slate-400"><flux:icon.photo class="h-8 w-8" /></div>
                            </div>
                            <div class="space-y-3 border-t border-slate-200 p-5 dark:border-slate-800">
                                <div><p class="font-black text-slate-950 dark:text-white">{{ $logo['label'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $logo['help'] }}</p></div>
                                <flux:field><flux:label>Ruta del archivo</flux:label><flux:input wire:model.live.debounce.500ms="{{ $logo['model'] }}" /><flux:error name="{{ $logo['model'] }}" /></flux:field>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div x-show="tab === 'textos'" x-cloak class="p-5 sm:p-7">
                <div class="grid gap-6 xl:grid-cols-[1fr_18rem]">
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-xl font-black text-slate-950 dark:text-white">Texto oficial del certificado</h2>
                            <p class="mt-1 text-sm text-slate-500">Conserva las variables entre llaves. El sistema reemplaza cada una al generar el documento.</p>
                        </div>
                        <flux:field>
                            <flux:label>Texto de certificación</flux:label>
                            <flux:textarea wire:model="texto_certificado" rows="9" class="font-mono text-sm leading-6" />
                            <flux:error name="texto_certificado" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Leyenda inferior de legalización</flux:label>
                            <flux:textarea wire:model="leyenda_certificado" rows="4" />
                            <flux:error name="leyenda_certificado" />
                        </flux:field>
                    </div>
                    <aside class="rounded-[1.5rem] border border-blue-100 bg-blue-50/70 p-5 dark:border-blue-900/40 dark:bg-blue-950/20">
                        <p class="font-black text-blue-950 dark:text-blue-100">Variables disponibles</p>
                        <p class="mt-1 text-xs leading-5 text-blue-700 dark:text-blue-300">Haz clic para copiar y pégala donde corresponda.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach (['{NOMBRE}', '{CURP}', '{ACREDITACION}', '{PLANTEL}', '{ACUERDO}', '{FECHA_ACUERDO}', '{CCT}', '{MODALIDAD}'] as $variable)
                                <button type="button" x-on:click="navigator.clipboard.writeText('{{ $variable }}')" class="rounded-xl border border-blue-200 bg-white px-3 py-2 font-mono text-xs font-black text-blue-800 shadow-sm transition hover:-translate-y-0.5 dark:border-blue-800 dark:bg-slate-950 dark:text-blue-200">{{ $variable }}</button>
                            @endforeach
                        </div>
                        <div class="mt-5 rounded-2xl bg-white/80 p-4 text-xs leading-5 text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">
                            <p class="font-black text-slate-900 dark:text-white">Vista de control</p>
                            <p class="mt-2">Plantel: <strong>{{ $nombre_plantel_oficial ?: 'Sin configurar' }}</strong></p>
                            <p>Acuerdo: <strong>{{ $numero_acuerdo ?: 'Sin configurar' }}</strong></p>
                            <p>Modalidad: <strong>{{ $modalidad ?: 'Sin configurar' }}</strong></p>
                        </div>
                    </aside>
                </div>
            </div>

            <div x-show="tab === 'firmantes'" x-cloak class="p-5 sm:p-7">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">Firmantes por vigencia</h2>
                        <p class="mt-1 text-sm text-slate-500">Cada periodo conserva su autoridad correspondiente para emitir documentos históricos correctamente.</p>
                    </div>
                    <a href="{{ route('misrutas.autoridades') }}" wire:navigate class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900">
                        <flux:icon.landmark class="h-4 w-4" /> Administrar autoridades
                    </a>
                </div>

                <div class="space-y-5">
                    @foreach($roles as $rol => $cargoPredeterminado)
                        @php
                            $tipoActual = $firmantes[$rol]['tipo'] ?? 'persona';
                            $esJefe = $rol === \App\Models\FirmanteMediaSuperior::ROL_JEFE_REGISTRO;
                            $permiteArchivos = in_array($rol, [\App\Models\FirmanteMediaSuperior::ROL_DIRECTOR, \App\Models\FirmanteMediaSuperior::ROL_JEFE_REGISTRO], true);
                            $coleccion = $tipoActual === 'persona' ? $this->personas : ($tipoActual === 'autoridad' ? $this->autoridades : $this->directores);
                        @endphp
                        <article class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#006492] text-white"><flux:icon.user-circle class="h-5 w-5" /></span>
                                    <div><p class="font-black text-slate-950 dark:text-white">{{ $cargoPredeterminado }}</p><p class="text-xs text-slate-500">Rol técnico: {{ $rol }}</p></div>
                                </div>
                                <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ filled($firmantes[$rol]['id'] ?? null) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' }}">
                                    {{ filled($firmantes[$rol]['id'] ?? null) ? 'Configurado' : 'Pendiente' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-6">
                                <flux:field>
                                    <flux:label>Origen</flux:label>
                                    <flux:select wire:model.live="firmantes.{{ $rol }}.tipo">
                                        <flux:select.option value="persona">Personal</flux:select.option>
                                        <flux:select.option value="director">Directores del plantel</flux:select.option>
                                        @if($esJefe)<flux:select.option value="autoridad">Autoridades</flux:select.option>@endif
                                    </flux:select>
                                </flux:field>
                                <flux:field class="xl:col-span-2">
                                    <flux:label>Firmante</flux:label>
                                    <flux:select wire:model="firmantes.{{ $rol }}.id">
                                        <flux:select.option value="">Sin configurar</flux:select.option>
                                        @foreach($coleccion as $persona)
                                            <flux:select.option value="{{ $persona->id }}">{{ trim($persona->titulo.' '.$persona->nombre.' '.$persona->apellido_paterno.' '.$persona->apellido_materno) }}{{ filled($persona->cargo ?? null) ? ' · '.$persona->cargo : '' }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="firmantes.{{ $rol }}.id" />
                                </flux:field>
                                <flux:field class="xl:col-span-3"><flux:label>Cargo impreso</flux:label><flux:input wire:model="firmantes.{{ $rol }}.cargo" /><flux:error name="firmantes.{{ $rol }}.cargo" /></flux:field>
                                <flux:field class="xl:col-span-3"><flux:label>Vigente desde</flux:label><flux:select wire:model="firmantes.{{ $rol }}.ciclo_desde_id"><flux:select.option value="">Sin límite</flux:select.option>@foreach($this->ciclos as $ciclo)<flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>@endforeach</flux:select><flux:error name="firmantes.{{ $rol }}.ciclo_desde_id" /></flux:field>
                                <flux:field class="xl:col-span-3"><flux:label>Vigente hasta</flux:label><flux:select wire:model="firmantes.{{ $rol }}.ciclo_hasta_id"><flux:select.option value="">Sin límite</flux:select.option>@foreach($this->ciclos as $ciclo)<flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>@endforeach</flux:select><flux:error name="firmantes.{{ $rol }}.ciclo_hasta_id" /></flux:field>
                            </div>
                            @if($permiteArchivos)
                                @include('livewire.media-superior.partials.carga-firma-documental', ['rol' => $rol])
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="sticky bottom-4 z-20 flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-2xl bg-[#006492] px-6 py-3.5 text-sm font-black text-white shadow-xl shadow-blue-500/25 transition hover:-translate-y-0.5 hover:bg-[#005474] disabled:opacity-60">
                <flux:icon.check class="h-5 w-5" />
                <span wire:loading.remove wire:target="guardar">Guardar configuración</span>
                <span wire:loading wire:target="guardar">Guardando cambios…</span>
            </button>
        </div>
    </form>
</div>
