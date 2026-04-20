<div class="space-y-6">
    @if (session('success'))
        <div
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">
                            Editar inscripción
                        </h1>

                        @if ($esBachillerato)
                            <flux:badge color="indigo">Bachillerato</flux:badge>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Actualiza los datos del alumno y su asignación escolar.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button variant="ghost" wire:click="cancelar">
                        Regresar
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="actualizarInscripcion" class="space-y-4">
        {{-- DATOS PERSONALES --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos personales</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Información básica del alumno.</p>
                </div>

                <span
                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    Identidad
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <flux:field class="xl:col-span-2">
                    <flux:label badge="Requerido">CURP *</flux:label>
                    <flux:input wire:model.live.debounce.500ms="curp" maxlength="18" placeholder="Ingresa la CURP" />
                    <flux:error name="curp" />
                    @if ($curpError)
                        <p class="mt-2 text-xs font-semibold text-amber-600">{{ $curpError }}</p>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label badge="Automático">Matrícula</flux:label>
                    <flux:input wire:model="matricula" readonly placeholder="Se genera automáticamente" />
                    <flux:error name="matricula" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Folio</flux:label>
                    <flux:input wire:model="folio" placeholder="Opcional" />
                    <flux:error name="folio" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Nombre(s) *</flux:label>
                    <flux:input wire:model="nombre" placeholder="Nombre(s)" />
                    <flux:error name="nombre" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Apellido paterno *</flux:label>
                    <flux:input wire:model="apellido_paterno" placeholder="Apellido paterno" />
                    <flux:error name="apellido_paterno" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Apellido materno</flux:label>
                    <flux:input wire:model="apellido_materno" placeholder="Apellido materno" />
                    <flux:error name="apellido_materno" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha de nacimiento *</flux:label>
                    <flux:input type="date" wire:model="fecha_nacimiento" />
                    <flux:error name="fecha_nacimiento" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Género *</flux:label>
                    <flux:select wire:model="genero">
                        <flux:select.option value="">Selecciona una opción</flux:select.option>
                        <flux:select.option value="H">Hombre</flux:select.option>
                        <flux:select.option value="M">Mujer</flux:select.option>
                    </flux:select>
                    <flux:error name="genero" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Fecha de inscripción *</flux:label>
                    <flux:input type="date" wire:model="fecha_inscripcion" />
                    <flux:error name="fecha_inscripcion" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Ciclo escolar *</flux:label>
                    <flux:select wire:model="ciclo_id">
                        <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                        @foreach ($ciclos as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->ciclo }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="ciclo_id" />
                </flux:field>
            </div>
        </div>

        {{-- ASIGNACIÓN ESCOLAR --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Asignación escolar</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">En bachillerato el grupo depende del
                        semestre.</p>
                </div>

                <span
                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    Académico
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <flux:field>
                    <flux:label badge="Requerido">Nivel *</flux:label>
                    <flux:select wire:model.live="nivel_id">
                        <flux:select.option value="">Selecciona un nivel</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">
                                {{ $nivel->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="nivel_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Grado *</flux:label>
                    <flux:select wire:model.live="grado_id" wire:key="grado-{{ $InscripcionId }}-{{ $nivel_id }}"
                        :disabled="!$nivel_id || $grados->isEmpty()">
                        <flux:select.option value="">Selecciona un grado</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ (string) $grado->id }}">
                                {{ $grado->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grado_id" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requerido">Generación *</flux:label>
                    <flux:select wire:model.live="generacion_id"
                        wire:key="generacion-{{ $InscripcionId }}-{{ $grado_id }}"
                        :disabled="!$grado_id || $generaciones->isEmpty()">
                        <flux:select.option value="">Selecciona una generación</flux:select.option>
                        @foreach ($generaciones as $generacion)
                            <flux:select.option value="{{ (string) $generacion->id }}">
                                {{ $generacion->label ?? $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="generacion_id" />
                </flux:field>

                @if ($esBachillerato)
                    <flux:field>
                        <flux:label badge="Requerido">Semestre *</flux:label>
                        <flux:select wire:model.live="semestre_id"
                            wire:key="semestre-{{ $InscripcionId }}-{{ $generacion_id }}"
                            :disabled="!$generacion_id || $semestres->isEmpty()">
                            <flux:select.option value="">Selecciona un semestre</flux:select.option>
                            @foreach ($semestres as $semestre)
                                <flux:select.option value="{{ (string) $semestre->id }}">
                                    Semestre {{ $semestre->numero }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="semestre_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label badge="Requerido">Grupo *</flux:label>
                    <flux:select wire:model.live="grupo_id"
                        wire:key="grupo-{{ $InscripcionId }}-{{ $generacion_id }}-{{ $semestre_id }}"
                        :disabled="!$generacion_id || ($esBachillerato && !$semestre_id) || empty($grupos)">
                        <flux:select.option value="">Selecciona un grupo</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ (string) $grupo['id'] }}">
                                {{ $grupo['label'] }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="grupo_id" />
                </flux:field>
            </div>

            @if ($esBachillerato)
                <div class="mt-4 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-700">
                    En bachillerato primero se selecciona la <b>generación</b>, después el <b>semestre</b> y al final el
                    <b>grupo</b>.
                </div>
            @endif
        </div>

        {{-- NACIMIENTO --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos de nacimiento</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Lugar de nacimiento del alumno.</p>
                </div>

                <span
                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    Opcional
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <flux:field>
                    <flux:label badge="Opcional">País de nacimiento</flux:label>
                    <flux:input wire:model="pais_nacimiento" placeholder="País de nacimiento" />
                    <flux:error name="pais_nacimiento" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Estado de nacimiento</flux:label>
                    <flux:input wire:model="estado_nacimiento" placeholder="Estado de nacimiento" />
                    <flux:error name="estado_nacimiento" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Lugar de nacimiento</flux:label>
                    <flux:input wire:model="lugar_nacimiento" placeholder="Lugar de nacimiento" />
                    <flux:error name="lugar_nacimiento" />
                </flux:field>
            </div>
        </div>

        {{-- TUTOR Y DOMICILIO --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Tutor y domicilio</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Selecciona el tutor y captura la
                        dirección.</p>
                </div>

                <span
                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    Contacto
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <flux:field class="xl:col-span-2">
                    <flux:label badge="Opcional">Tutor</flux:label>
                    <flux:select wire:model="tutor_id">
                        <flux:select.option value="">Selecciona un tutor</flux:select.option>
                        @foreach ($tutores as $tutor)
                            <flux:select.option value="{{ $tutor->id }}">
                                {{ trim(($tutor->nombre ?? '') . ' ' . ($tutor->apellido_paterno ?? '') . ' ' . ($tutor->apellido_materno ?? '')) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="tutor_id" />
                </flux:field>

                <div class="md:col-span-2 flex items-end">
                    <label
                        class="inline-flex w-full items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                        <input type="checkbox" wire:model.live="copiar_direccion_tutor"
                            class="rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                        Copiar dirección del tutor
                    </label>
                </div>

                <flux:field>
                    <flux:label badge="Opcional">Calle</flux:label>
                    <flux:input wire:model="calle" placeholder="Calle" />
                    <flux:error name="calle" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Número exterior</flux:label>
                    <flux:input wire:model="numero_exterior" placeholder="Número exterior" />
                    <flux:error name="numero_exterior" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Número interior</flux:label>
                    <flux:input wire:model="numero_interior" placeholder="Número interior" />
                    <flux:error name="numero_interior" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Colonia</flux:label>
                    <flux:input wire:model="colonia" placeholder="Colonia" />
                    <flux:error name="colonia" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Código postal</flux:label>
                    <flux:input wire:model="codigo_postal" placeholder="Código postal" />
                    <flux:error name="codigo_postal" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Municipio</flux:label>
                    <flux:input wire:model="municipio" placeholder="Municipio" />
                    <flux:error name="municipio" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Estado de residencia</flux:label>
                    <flux:input wire:model="estado_residencia" placeholder="Estado de residencia" />
                    <flux:error name="estado_residencia" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Opcional">Ciudad de residencia</flux:label>
                    <flux:input wire:model="ciudad_residencia" placeholder="Ciudad de residencia" />
                    <flux:error name="ciudad_residencia" />
                </flux:field>
            </div>
        </div>

        {{-- FOTO --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Fotografía</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Puedes conservar la actual o subir una
                        nueva.</p>
                </div>

                <span
                    class="inline-flex items-center rounded-full border border-zinc-200 px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    Opcional
                </span>
            </div>

            <div x-data="{
                preview: null,
                nombreArchivo: '',
                usarTemporal(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.nombreArchivo = file.name;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.preview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                },
                limpiar() {
                    this.preview = null;
                    this.nombreArchivo = '';
                    const input = document.getElementById('foto_editar');
                    if (input) input.value = '';
                }
            }" x-on:foto-limpiada.window="limpiar()" class="mt-4">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                    <div class="flex items-center justify-center">
                        <div class="relative">
                            <div wire:loading.flex wire:target="foto"
                                class="absolute inset-0 z-20 hidden items-center justify-center rounded-[26px] bg-white/70 backdrop-blur-sm dark:bg-neutral-950/70">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="relative h-12 w-12">
                                        <div
                                            class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40">
                                        </div>
                                        <div
                                            class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                                        </div>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        Subiendo foto...
                                    </p>
                                </div>
                            </div>

                            <div
                                class="group relative h-52 w-52 overflow-hidden rounded-[26px] border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 shadow-lg dark:border-neutral-700 dark:from-neutral-800 dark:to-neutral-900">
                                <template x-if="preview">
                                    <img :src="preview" alt="Vista previa"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                </template>

                                @if (!empty($foto) && is_object($foto))
                                    <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa temporal"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                        x-show="!preview">
                                @elseif (!empty($foto_actual))
                                    <img src="{{ asset('storage/' . $foto_actual) }}" alt="Foto actual"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                        x-show="!preview">
                                @else
                                    <div x-show="!preview"
                                        class="flex h-full w-full flex-col items-center justify-center px-4 text-center">
                                        <div
                                            class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-md dark:bg-neutral-800">
                                            <flux:icon.camera class="h-8 w-8 text-slate-400 dark:text-slate-500" />
                                        </div>
                                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                                            Sin fotografía
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                            Aquí se mostrará la imagen seleccionada
                                        </p>
                                    </div>
                                @endif

                                <div
                                    class="absolute left-3 top-3 rounded-full bg-black/55 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                                    Foto
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center">
                        <label for="foto_editar"
                            class="group relative flex cursor-pointer flex-col items-center justify-center rounded-[26px] border-2 border-dashed border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 px-6 py-8 text-center transition duration-300 hover:border-sky-400 hover:shadow-lg hover:shadow-sky-500/10 dark:border-sky-900/40 dark:from-sky-950/20 dark:via-neutral-900 dark:to-indigo-950/20">
                            <input id="foto_editar" type="file" wire:model="foto"
                                accept="image/png,image/jpeg,image/jpg" class="hidden"
                                @change="usarTemporal($event)">

                            <div
                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                                <flux:icon.cloud-arrow-up class="h-8 w-8" />
                            </div>

                            <h4 class="text-sm font-bold text-slate-800 dark:text-white">
                                Haz clic para subir una nueva fotografía
                            </h4>

                            <p class="mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">
                                Si no subes una imagen, se conservará la fotografía actual.
                            </p>

                            <div
                                class="mt-4 inline-flex items-center rounded-full bg-white px-4 py-2 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100 dark:bg-neutral-800 dark:text-sky-300 dark:ring-sky-900/30">
                                JPG, JPEG o PNG
                            </div>
                        </label>

                        <div x-show="nombreArchivo" x-transition class="mt-4">
                            <div
                                class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <flux:icon.check-circle class="h-4 w-4" />
                                <span class="truncate" x-text="nombreArchivo"></span>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <label for="foto_editar"
                                class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:scale-[1.01]">
                                <flux:icon.image-plus class="mr-2 h-4 w-4" />
                                Seleccionar foto
                            </label>

                            <button type="button" @click="limpiar()" wire:click="quitarFotoTemporal"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:bg-neutral-700">
                                <flux:icon.trash-2 class="mr-2 h-4 w-4" />
                                Quitar
                            </button>
                        </div>

                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            Recomendación: usa una imagen vertical, con buena iluminación y fondo limpio.
                        </p>

                        @error('foto')
                            <p class="mt-3 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div wire:loading.flex wire:target="actualizarInscripcion"
            class="absolute inset-0 z-50 hidden items-center justify-center rounded-[28px] bg-white/75 backdrop-blur-sm dark:bg-neutral-950/75">
            <div
                class="flex min-w-[260px] flex-col items-center rounded-3xl border border-sky-100 bg-white/95 px-8 py-7 shadow-2xl shadow-sky-500/10 dark:border-sky-900/40 dark:bg-neutral-950/95">
                <div class="relative mb-4 flex h-16 w-16 items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-4 border-sky-200 dark:border-sky-900/40"></div>
                    <div
                        class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-sky-500 border-r-indigo-500">
                    </div>
                    <div
                        class="h-8 w-8 rounded-full bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-600 shadow-lg shadow-sky-500/30">
                    </div>
                </div>

                <h3 class="text-base font-bold text-slate-800 dark:text-white">
                    Actualizando inscripción
                </h3>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Guardando cambios del alumno...
                </p>
            </div>
        </div>


        <div class="flex justify-end gap-2 pt-2">
            <flux:button variant="ghost" type="button" wire:click="cancelar">
                Cancelar
            </flux:button>

            <flux:button variant="primary" type="submit" wire:loading.attr="disabled"
                wire:target="actualizarInscripcion,foto,curp">
                <span wire:loading.remove wire:target="actualizarInscripcion">Guardar cambios</span>
                <span wire:loading wire:target="actualizarInscripcion">Actualizando...</span>
            </flux:button>
        </div>
    </form>
</div>
