<div x-data="{ show: false }" x-cloak x-show="show" x-trap.noscroll="show" @abrir-modal-editar.window="show = true"
    @cerrar-modal-editar.window="show = false" @keydown.escape.window="show = false; $wire.cerrarModal()"
    class="fixed inset-0 z-50 flex items-center justify-center">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-neutral-900/70 backdrop-blur-sm" x-show="show" x-transition.opacity
        @click.self="show = false; $wire.cerrarModal()"></div>

    {{-- Modal --}}
    <div class="relative mx-4 flex max-h-[90vh] w-[96vw] max-w-6xl flex-col overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900"
        role="dialog" aria-modal="true" x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95" wire:ignore.self>

        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500"></div>

        {{-- Header --}}
        <div
            class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/95 sm:px-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                    Editar inscripción
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Actualiza los datos del alumno y su asignación escolar.
                </p>
            </div>

            <button type="button" @click="show = false; $wire.cerrarModal()"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-neutral-800 dark:hover:text-slate-200">
                <flux:icon.x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">
            <form wire:submit.prevent="actualizarInscripcion" class="space-y-6 p-5 sm:p-6 lg:p-8">
                {{-- DATOS PERSONALES --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                            <flux:icon.user class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Datos personales
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Información básica del alumno.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <flux:input wire:model.live.debounce.500ms="curp" label="CURP" maxlength="18" />
                            @error('curp')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                            @if ($curpError)
                                <p class="mt-2 text-xs font-semibold text-amber-600">{{ $curpError }}</p>
                            @endif
                        </div>

                        <div>
                            <flux:input wire:model="matricula" label="Matrícula" readonly />
                            @error('matricula')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="folio" label="Folio" />
                            @error('folio')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="nombre" label="Nombre(s)" />
                            @error('nombre')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="apellido_paterno" label="Apellido paterno" />
                            @error('apellido_paterno')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="apellido_materno" label="Apellido materno" />
                            @error('apellido_materno')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input type="date" wire:model="fecha_nacimiento" label="Fecha de nacimiento" />
                            @error('fecha_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:select wire:model="genero" label="Género">
                                <flux:select.option value="">Selecciona una opción</flux:select.option>
                                <flux:select.option value="H">Hombre</flux:select.option>
                                <flux:select.option value="M">Mujer</flux:select.option>
                            </flux:select>
                            @error('genero')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input type="date" wire:model="fecha_inscripcion" label="Fecha de inscripción" />
                            @error('fecha_inscripcion')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:select wire:model="ciclo_id" label="Ciclo escolar">
                                <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                                @foreach ($ciclos as $ciclo)
                                    <flux:select.option value="{{ $ciclo->id }}">
                                        {{ $ciclo->ciclo }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('ciclo_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- ASIGNACIÓN ESCOLAR --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                            <flux:icon.academic-cap class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Asignación escolar
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                En bachillerato el grupo depende del semestre.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div>
                            <flux:select wire:model.live="nivel_id" label="Nivel">
                                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                                @foreach ($niveles as $nivel)
                                    <flux:select.option value="{{ $nivel->id }}">
                                        {{ $nivel->nombre }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('nivel_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:select wire:model.live="grado_id" label="Grado"
                                :disabled="!$nivel_id || $grados->isEmpty()">
                                <flux:select.option value="">Selecciona un grado</flux:select.option>
                                @foreach ($grados as $grado)
                                    <flux:select.option value="{{ $grado->id }}">
                                        {{ $grado->nombre }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('grado_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:select wire:model.live="generacion_id" label="Generación"
                                :disabled="!$grado_id || $generaciones->isEmpty()">
                                <flux:select.option value="">Selecciona una generación</flux:select.option>
                                @foreach ($generaciones as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">
                                        {{ $generacion->label ?? $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('generacion_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($esBachillerato)
                            <div>
                                <flux:select wire:model.live="semestre_id" label="Semestre"
                                    :disabled="!$generacion_id || $semestres->isEmpty()">
                                    <flux:select.option value="">Selecciona un semestre</flux:select.option>
                                    @foreach ($semestres as $semestre)
                                        <flux:select.option value="{{ $semestre->id }}">
                                            Semestre {{ $semestre->numero }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('semestre_id')
                                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div>
                            <flux:select wire:model.live="grupo_id" label="Grupo"
                                :disabled="!$generacion_id || ($esBachillerato && !$semestre_id) || empty($grupos)">
                                <flux:select.option value="">Selecciona un grupo</flux:select.option>
                                @foreach ($grupos as $grupo)
                                    <flux:select.option value="{{ $grupo['id'] }}">
                                        {{ $grupo['label'] }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('grupo_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @if ($esBachillerato)
                        <div
                            class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                            En bachillerato primero se selecciona la <b>generación</b>, después el <b>semestre</b> y
                            al final el <b>grupo</b>.
                        </div>
                    @endif
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- NACIMIENTO --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <flux:icon.map-pin class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Datos de nacimiento
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Lugar de nacimiento del alumno.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <flux:input wire:model="pais_nacimiento" label="País de nacimiento" />
                            @error('pais_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="estado_nacimiento" label="Estado de nacimiento" />
                            @error('estado_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="lugar_nacimiento" label="Lugar de nacimiento" />
                            @error('lugar_nacimiento')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- TUTOR Y DOMICILIO --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                            <flux:icon.home class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Tutor y domicilio
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Selecciona el tutor y captura la dirección.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <flux:select wire:model="tutor_id" label="Tutor">
                                <flux:select.option value="">Selecciona un tutor</flux:select.option>
                                @foreach ($tutores as $tutor)
                                    <flux:select.option value="{{ $tutor->id }}">
                                        {{ trim(($tutor->nombre ?? '') . ' ' . ($tutor->apellido_paterno ?? '') . ' ' . ($tutor->apellido_materno ?? '')) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            @error('tutor_id')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 flex items-end">
                            <label
                                class="inline-flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200">
                                <input type="checkbox" wire:model.live="copiar_direccion_tutor"
                                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                Copiar dirección del tutor
                            </label>
                        </div>

                        <div>
                            <flux:input wire:model="calle" label="Calle" />
                            @error('calle')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="numero_exterior" label="Número exterior" />
                            @error('numero_exterior')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="numero_interior" label="Número interior" />
                            @error('numero_interior')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="colonia" label="Colonia" />
                            @error('colonia')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="codigo_postal" label="Código postal" />
                            @error('codigo_postal')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="municipio" label="Municipio" />
                            @error('municipio')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="estado_residencia" label="Estado de residencia" />
                            @error('estado_residencia')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <flux:input wire:model="ciudad_residencia" label="Ciudad de residencia" />
                            @error('ciudad_residencia')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div
                    class="my-6 h-px w-full bg-gradient-to-r from-transparent via-slate-300 to-transparent dark:via-neutral-700">
                </div>

                {{-- FOTO --}}
                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-pink-100 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300">
                            <flux:icon.camera class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white">
                                Fotografía
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Puedes conservar la actual o subir una nueva.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_auto_auto]">
                        <div>
                            <input type="file" wire:model="foto"
                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200" />
                            @error('foto')
                                <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($foto_actual && !$foto)
                            <div class="flex items-center justify-center">
                                <img src="{{ asset('storage/' . $foto_actual) }}" alt="Foto actual"
                                    class="h-20 w-20 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                            </div>
                        @endif

                        @if ($foto)
                            <div class="flex items-center justify-center">
                                <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa"
                                    class="h-20 w-20 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                            </div>
                        @endif
                    </div>
                </section>

                <div
                    class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-end">
                    <flux:button type="button" variant="ghost" wire:click="cerrarModal"
                        class="cursor-pointer rounded-2xl">
                        Cancelar
                    </flux:button>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="actualizar,foto,curp" class="cursor-pointer rounded-2xl">
                        <span wire:loading.remove wire:target="actualizar">Guardar cambios</span>
                        <span wire:loading wire:target="actualizar">Actualizando...</span>
                    </flux:button>
                </div>
            </form>
        </div>

        {{-- Loader --}}
        <div wire:loading.flex wire:target="actualizar"
            class="absolute inset-0 hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-neutral-900/70">
            <div
                class="rounded-3xl border border-slate-200 bg-white px-6 py-5 text-center shadow-xl dark:border-neutral-700 dark:bg-neutral-900">
                <div class="mx-auto mb-3 h-10 w-10 animate-spin rounded-full border-4 border-sky-200 border-t-sky-600">
                </div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Actualizando inscripción...
                </p>
            </div>
        </div>
    </div>
</div>
