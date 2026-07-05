<div id="panel-asignacion-materia" x-data="{
    async confirmarEliminar(id, nombre, contexto) {
            await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar esta carga académica?',
                html: `
                    <div style='text-align:left'>
                        <div style='margin-bottom:12px;padding:12px 14px;border:1px solid #dbeafe;border-radius:14px;background:#eff6ff'>
                            <div style='font-weight:800;color:#0f172a'>${nombre}</div>
                            <div style='margin-top:3px;font-size:12px;color:#64748b'>${contexto}</div>
                        </div>
                        <p style='font-size:14px;color:#475569'>
                            Se quitará únicamente del ciclo seleccionado. Esta acción es permanente y solo procede cuando no existen horarios, calificaciones ni auditoría.
                        </p>
                    </div>
                `,
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonText: 'Sí, eliminar carga',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                allowEscapeKey: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        await this.$wire.call('eliminar', id);
                    } catch (error) {
                        Swal.showValidationMessage('No fue posible eliminar la carga. Revisa la información e inténtalo nuevamente.');
                    }
                }
            });
        },

        async confirmarArchivar(id, nombre, contexto) {
            await Swal.fire({
                icon: 'question',
                title: 'Archivar carga académica',
                html: `
                    <div style='text-align:left'>
                        <div style='margin-bottom:12px;padding:12px 14px;border:1px solid #fde68a;border-radius:14px;background:#fffbeb'>
                            <div style='font-weight:800;color:#0f172a'>${nombre}</div>
                            <div style='margin-top:3px;font-size:12px;color:#64748b'>${contexto}</div>
                        </div>
                        <p style='font-size:14px;color:#475569'>
                            La carga dejará de estar activa, pero conservará horarios, calificaciones y movimientos históricos.
                        </p>
                    </div>
                `,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Sí, archivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                allowEscapeKey: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        await this.$wire.call('archivar', id);
                    } catch (error) {
                        Swal.showValidationMessage('No fue posible archivar la carga. Inténtalo nuevamente.');
                    }
                }
            });
        }
}" class="space-y-6">
    {{-- Loader al preparar la edición --}}
    <div wire:loading.flex wire:target="editar"
        class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
        <div
            class="w-full max-w-sm overflow-hidden rounded-[2rem] border border-white/20 bg-white/95 shadow-2xl shadow-blue-950/30 dark:bg-slate-900/95">
            <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>
            <div class="p-7 text-center">
                <div class="relative mx-auto mb-5 h-16 w-16">
                    <div
                        class="absolute inset-0 animate-spin rounded-full border-4 border-blue-100 border-r-[#88AC2E] border-t-[#006492] dark:border-slate-700">
                    </div>
                    <div
                        class="absolute inset-[10px] flex items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg">
                        <flux:icon.pencil-square class="h-6 w-6" />
                    </div>
                </div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Preparando la edición</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Cargando materia, grupo y profesor responsable…
                </p>
            </div>
        </div>
    </div>

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>
        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Cargas académicas por ciclo</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Cada ciclo conserva sus propios profesores, materias y horarios. Los ciclos anteriores no se
                        sobrescriben.
                    </p>
                </div>
                <span
                    class="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                    {{ $nivel?->nombre }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <flux:field>
                    <flux:label>Ciclo de trabajo</flux:label>
                    <flux:select wire:model.live="ciclo_escolar_id">
                        @foreach ($this->ciclosEscolares as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="ciclo_escolar_id" />
                </flux:field>

                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                        Protección histórica</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Eliminar solo está disponible cuando la carga no tiene horarios, calificaciones ni auditoría.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <p class="text-xs font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">Estado
                        inicial</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Las nuevas cargas quedan como borrador hasta que el administrador las confirme.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @if (auth()->user()?->is_admin)
        <section
            class="rounded-[1.6rem] border border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50 p-5 shadow-sm dark:border-indigo-900/50 dark:from-indigo-950/25 dark:to-blue-950/20 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-2xl">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Preparar este nivel desde otro ciclo
                    </h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        Crea nuevas cargas con IDs nuevos. Puedes copiar solo materias, también docentes o además días y
                        horas.
                    </p>
                </div>

                <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 xl:max-w-3xl xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Ciclo origen</flux:label>
                        <flux:select wire:model="ciclo_origen_id">
                            <flux:select.option value="">Selecciona</flux:select.option>
                            @foreach ($this->ciclosEscolares as $ciclo)
                                @if ((int) $ciclo->id !== (int) $ciclo_escolar_id)
                                    <flux:select.option value="{{ $ciclo->id }}">
                                        {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>
                        <flux:error name="ciclo_origen_id" />
                    </flux:field>

                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-2xl border border-white/80 bg-white/70 px-4 py-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
                        <input type="checkbox" wire:model="copiar_profesores"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">Copiar docentes</span>
                    </label>

                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-2xl border border-white/80 bg-white/70 px-4 py-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-900/60">
                        <input type="checkbox" wire:model="copiar_horarios"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-200">Copiar horarios</span>
                    </label>

                    <button type="button" wire:click="copiarDesdeCiclo" wire:loading.attr="disabled"
                        wire:confirm="Se crearán cargas nuevas para este nivel y ciclo. No se modificará el ciclo origen. ¿Continuar?"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 disabled:opacity-60">
                        <flux:icon.document-duplicate class="h-5 w-5" />
                        <span wire:loading.remove wire:target="copiarDesdeCiclo">Preparar ciclo</span>
                        <span wire:loading wire:target="copiarDesdeCiclo">Copiando…</span>
                    </button>
                </div>
            </div>
        </section>
    @endif

    {{-- Nueva carga a todo el ancho --}}
    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div
            class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50/60 px-5 py-5 dark:border-slate-800 dark:from-slate-900 dark:via-slate-950 dark:to-blue-950/20 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-blue-500/20">
                    <flux:icon.academic-cap class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">
                        Nueva carga académica
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Selecciona el contexto, la materia y el docente. El horario puede asignarse posteriormente.
                    </p>
                </div>
            </div>

            <span
                class="w-fit rounded-full bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                Se guardará como borrador
            </span>
        </div>

        <div class="p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-12 xl:items-end">
                <div class="xl:col-span-4">
                    <flux:field>
                        <flux:label>Grado, grupo y generación</flux:label>
                        <flux:select wire:model.live="grupo_id">
                            <flux:select.option value="">Selecciona un grupo</flux:select.option>
                            @foreach ($this->grupos as $grupo)
                                <flux:select.option value="{{ $grupo->id }}">
                                    {{ $grupo->grado?->nombre ?? 'Sin grado' }} · Grupo
                                    {{ $grupo->asignacionGrupo?->nombre ?? '—' }} ·
                                    {{ $grupo->generacion?->anio_ingreso ?? '—' }}-{{ $grupo->generacion?->anio_egreso ?? '—' }}{{ $grupo->semestre ? ' · ' . $grupo->semestre->numero . '° semestre' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="grupo_id" />
                    </flux:field>
                </div>

                <div class="xl:col-span-3">
                    <flux:field>
                        <flux:label>Materia</flux:label>
                        <flux:select wire:model="materia_id" :disabled="blank($grupo_id)">
                            <flux:select.option value="">Selecciona una materia</flux:select.option>
                            @foreach ($this->materiasDisponibles as $materia)
                                <flux:select.option value="{{ $materia->id }}">
                                    {{ $materia->materia }}{{ $materia->clave ? ' · ' . $materia->clave : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="materia_id" />
                    </flux:field>
                </div>

                <div class="relative xl:col-span-3">
                    <flux:field>
                        <flux:label>Profesor responsable</flux:label>
                        <flux:input wire:model.live.debounce.300ms="buscarProfesor" icon="magnifying-glass"
                            placeholder="Puede quedar pendiente" autocomplete="off" />
                        <flux:error name="profesor_id" />
                    </flux:field>

                    @if ($buscarProfesor !== '' && blank($profesor_id))
                        <div
                            class="absolute z-30 mt-2 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900">
                            @forelse ($this->profesoresFiltrados as $profesor)
                                <button type="button" wire:click="seleccionarProfesor({{ $profesor['id'] }})"
                                    class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm font-bold text-slate-700 last:border-0 hover:bg-indigo-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-indigo-950/20">
                                    {{ $profesor['nombre'] }}
                                </button>
                            @empty
                                <p class="p-4 text-center text-sm text-slate-500">Sin coincidencias.</p>
                            @endforelse
                        </div>
                    @endif
                </div>

                <div class="xl:col-span-2">
                    <button type="button" wire:click="guardarMateria" wire:loading.attr="disabled"
                        class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl bg-[#006492] px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-[#005474] disabled:translate-y-0 disabled:opacity-60">
                        <flux:icon.check class="h-5 w-5" />
                        <span wire:loading.remove wire:target="guardarMateria">Guardar borrador</span>
                        <span wire:loading wire:target="guardarMateria">Guardando…</span>
                    </button>
                </div>
            </div>

            @if ($this->grupoSeleccionado)
                <div
                    class="mt-5 flex flex-wrap items-center gap-2 rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-xs font-bold text-slate-600 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-slate-300">
                    <span
                        class="rounded-full bg-white px-2.5 py-1 text-blue-700 shadow-sm dark:bg-slate-900 dark:text-blue-300">
                        {{ $this->grupoSeleccionado->grado?->nombre ?? 'Sin grado' }}
                    </span>
                    <span>Grupo {{ $this->grupoSeleccionado->asignacionGrupo?->nombre ?? '—' }}</span>
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <span>Generación
                        {{ $this->grupoSeleccionado->generacion?->anio_ingreso ?? '—' }}-{{ $this->grupoSeleccionado->generacion?->anio_egreso ?? '—' }}</span>
                    @if ($this->grupoSeleccionado->semestre)
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <span
                            class="text-violet-700 dark:text-violet-300">{{ $this->grupoSeleccionado->semestre->numero }}°
                            semestre</span>
                    @endif
                    <span class="ml-auto text-slate-500">{{ $this->materiasDisponibles->count() }} materia(s)
                        disponibles</span>
                </div>
            @endif
        </div>
    </section>


    {{-- Configuración del número de materias a promediar --}}
    <section x-data="{
        abierto: localStorage.getItem('asignacion-materias-promedio-abierto') !== 'false',
        alternar() {
            this.abierto = !this.abierto;
            localStorage.setItem('asignacion-materias-promedio-abierto', this.abierto ? 'true' : 'false');
        }
    }"
        class="overflow-hidden rounded-[1.75rem] border border-violet-200 bg-white shadow-sm dark:border-violet-900/50 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-violet-500 to-[#88AC2E]"></div>

        <button type="button" @click="alternar()"
            class="flex w-full flex-col gap-4 px-5 py-5 text-left transition hover:bg-slate-50/80 dark:hover:bg-slate-900/50 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-500/20">
                    <flux:icon.academic-cap class="h-6 w-6" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            Materias a promediar
                        </h3>
                        <span
                            class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-black text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                            Configuración académica
                        </span>
                    </div>
                    <p class="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
                        Define el número de materias por grado y semestre. En bachillerato, si no existe una
                        configuración, se tomarán automáticamente solo las materias con calificable = 1.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-black text-slate-500"
                    x-text="abierto ? 'Ocultar configuración' : 'Mostrar configuración'"></span>
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <svg class="h-5 w-5 transition-transform duration-200" :class="{ 'rotate-180': abierto }"
                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        </button>

        <div x-show="abierto" x-collapse x-cloak
            class="border-t border-slate-200 bg-slate-50/40 p-5 dark:border-slate-800 dark:bg-slate-900/30 sm:p-6">
            <livewire:materia-promediar :slug_nivel="$slug_nivel" :key="'materias-promediar-' . $slug_nivel" />
        </div>
    </section>

    <section
        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div
            class="border-b border-slate-200 bg-slate-50/80 px-5 py-5 dark:border-slate-800 dark:bg-slate-900/70 sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Revisión de cargas</h3>
                        @if ($this->tieneFiltrosActivos)
                            <span
                                class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-black text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">Vista
                                filtrada</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $this->resumenCargas['total'] }} registro(s) encontrados en el ciclo seleccionado.
                    </p>
                </div>

                @if (auth()->user()?->is_admin && $this->hayBorradoresFiltrados)
                    <button type="button" wire:click="confirmarTodas"
                        wire:confirm="¿Confirmar todas las cargas en borrador de este nivel?"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:bg-emerald-700">
                        <flux:icon.check-circle class="h-4 w-4" />
                        Confirmar todos los borradores
                    </button>
                @endif
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-950">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Resultados</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                        {{ $this->resumenCargas['total'] }}</p>
                </div>
                <div
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                        Activas</p>
                    <p class="mt-1 text-2xl font-black text-emerald-800 dark:text-emerald-200">
                        {{ $this->resumenCargas['activas'] }}</p>
                </div>
                <div
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <p class="text-[11px] font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">
                        Borradores</p>
                    <p class="mt-1 text-2xl font-black text-amber-800 dark:text-amber-200">
                        {{ $this->resumenCargas['borradores'] }}</p>
                </div>
                <div
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/20">
                    <p class="text-[11px] font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">Sin
                        horario</p>
                    <p class="mt-1 text-2xl font-black text-orange-800 dark:text-orange-200">
                        {{ $this->resumenCargas['sin_horario'] }}</p>
                </div>
                <div
                    class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/20">
                    <p class="text-[11px] font-black uppercase tracking-wide text-violet-700 dark:text-violet-300">
                        Docente pendiente</p>
                    <p class="mt-1 text-2xl font-black text-violet-800 dark:text-violet-200">
                        {{ $this->resumenCargas['sin_profesor'] }}</p>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-950 sm:px-6">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                        <flux:icon.funnel class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="font-black text-slate-900 dark:text-white">Filtros de consulta</p>
                        <p class="text-xs text-slate-500">Combina los filtros para localizar una carga específica.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        <span>Mostrar</span>
                        <select wire:model.live="porPaginaMaterias"
                            class="border-0 bg-transparent p-0 pr-7 text-xs font-black text-slate-700 focus:ring-0 dark:text-slate-200">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>

                    @if ($this->tieneFiltrosActivos)
                        <button type="button" wire:click="limpiarFiltros"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            <flux:icon.x-mark class="h-4 w-4" />
                            Limpiar filtros
                        </button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
                <div class="md:col-span-2 xl:col-span-2 2xl:col-span-2">
                    <flux:field>
                        <flux:label>Buscar</flux:label>
                        <flux:input wire:model.live.debounce.350ms="buscar" icon="magnifying-glass"
                            placeholder="Materia, clave, docente, grado o grupo" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Generación</flux:label>
                    <flux:select wire:model.live="filtro_generacion">
                        <flux:select.option value="">Todas</flux:select.option>
                        @foreach ($this->generacionesFiltro as $generacion)
                            <flux:select.option value="{{ $generacion->id }}">
                                {{ $generacion->nombre ?: $generacion->anio_ingreso . '-' . $generacion->anio_egreso }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <flux:select wire:model.live="filtro_estado">
                        <flux:select.option value="">Todos</flux:select.option>
                        <flux:select.option value="borrador">Borrador</flux:select.option>
                        <flux:select.option value="activa">Activa</flux:select.option>
                        <flux:select.option value="cerrada">Cerrada</flux:select.option>
                        <flux:select.option value="archivada">Archivada</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="filtro_grado">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($this->gradosFiltro as $grado)
                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                @if ($this->esBachillerato)
                    <flux:field>
                        <flux:label>Semestre</flux:label>
                        <flux:select wire:model.live="filtro_semestre">
                            <flux:select.option value="">Todos</flux:select.option>
                            @foreach ($this->semestresFiltro as $semestre)
                                <flux:select.option value="{{ $semestre->id }}">{{ $semestre->numero }}° semestre
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.live="filtro_grupo">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($this->gruposFiltro as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->grado?->nombre ?? '—' }} ·
                                {{ $grupo->asignacionGrupo?->nombre ?? '—' }}{{ $grupo->semestre ? ' · ' . $grupo->semestre->numero . '°' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Horario</flux:label>
                    <flux:select wire:model.live="filtro_horario">
                        <flux:select.option value="">Todos</flux:select.option>
                        <flux:select.option value="con">Con horario</flux:select.option>
                        <flux:select.option value="sin">Pendiente</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Docente</flux:label>
                    <flux:select wire:model.live="filtro_profesor">
                        <flux:select.option value="">Todos</flux:select.option>
                        <flux:select.option value="asignado">Asignado</flux:select.option>
                        <flux:select.option value="pendiente">Pendiente</flux:select.option>
                    </flux:select>
                </flux:field>
            </div>
        </div>

        @if ($this->asignacionesFiltradas->count() === 0)
            <div class="p-12 text-center">
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 dark:bg-slate-900">
                    <flux:icon.inbox class="h-8 w-8 text-slate-400" />
                </div>
                <p class="mt-4 font-black text-slate-800 dark:text-white">No hay cargas que coincidan.</p>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $this->tieneFiltrosActivos ? 'Ajusta o limpia los filtros para ampliar la consulta.' : 'Puedes capturarlas manualmente o prepararlas desde otro ciclo.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-100 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                                Materia</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                                Contexto académico</th>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                                Profesor responsable</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">
                                Horario</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">
                                Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($this->asignacionesFiltradas as $asignacion)
                            <tr wire:key="carga-academica-{{ $asignacion->id }}"
                                class="align-top transition hover:bg-slate-50 dark:hover:bg-slate-900/60">
                                <td class="px-4 py-4">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $asignacion->materia?->materia ?? 'Materia' }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $asignacion->materia?->clave ?: 'Sin clave' }}
                                        </span>
                                        @if ($asignacion->orden)
                                            <span class="text-[11px] font-semibold text-slate-400">Orden
                                                {{ $asignacion->orden }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-700 dark:text-slate-200">
                                    <p class="font-bold">{{ $asignacion->grupo?->grado?->nombre ?? '—' }} · Grupo
                                        {{ $asignacion->grupo?->asignacionGrupo?->nombre ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Generación
                                        {{ $asignacion->grupo?->generacion?->anio_ingreso ?? '—' }}-{{ $asignacion->grupo?->generacion?->anio_egreso ?? '—' }}
                                    </p>
                                    @if ($asignacion->grupo?->semestre)
                                        <span
                                            class="mt-2 inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-black text-violet-700 dark:bg-violet-950/30 dark:text-violet-300">
                                            {{ $asignacion->grupo->semestre->numero }}° semestre
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($asignacion->profesor)
                                        <p class="font-bold text-slate-700 dark:text-slate-200">
                                            {{ trim(($asignacion->profesor->titulo ?? '') . ' ' . ($asignacion->profesor->nombre ?? '') . ' ' . ($asignacion->profesor->apellido_paterno ?? '') . ' ' . ($asignacion->profesor->apellido_materno ?? '')) }}
                                        </p>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-black text-violet-700 dark:bg-violet-950/30 dark:text-violet-300">Docente
                                            pendiente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if ($asignacion->horarios->isNotEmpty())
                                        <span
                                            class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $asignacion->horarios->count() }}
                                            bloque(s)</span>
                                    @else
                                        <span
                                            class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">Pendiente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-black',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' =>
                                            $asignacion->estado === 'borrador',
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' =>
                                            $asignacion->estado === 'activa',
                                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' =>
                                            $asignacion->estado === 'cerrada',
                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300' =>
                                            $asignacion->estado === 'archivada',
                                    ])>
                                        {{ ucfirst($asignacion->estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <button type="button" wire:click="editar({{ $asignacion->id }})"
                                            wire:loading.attr="disabled" wire:target="editar"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-black text-blue-700 transition hover:-translate-y-0.5 hover:bg-blue-100 disabled:cursor-wait disabled:opacity-60">
                                            <flux:icon.pencil-square class="h-3.5 w-3.5" />
                                            Editar
                                        </button>

                                        @if (auth()->user()?->is_admin && $asignacion->estado === 'borrador')
                                            <button type="button" wire:click="confirmar({{ $asignacion->id }})"
                                                class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-black text-white transition hover:bg-emerald-700">
                                                Confirmar
                                            </button>
                                        @endif

                                        @if (auth()->user()?->is_admin && $asignacion->estado === 'activa')
                                            <button type="button" wire:click="cerrar({{ $asignacion->id }})"
                                                wire:confirm="¿Cerrar esta carga? Seguirá disponible para consulta histórica."
                                                class="rounded-lg bg-slate-700 px-2.5 py-1.5 text-[11px] font-black text-white transition hover:bg-slate-800">
                                                Cerrar
                                            </button>
                                        @endif

                                        @if (auth()->user()?->is_admin && in_array($asignacion->estado, ['cerrada', 'archivada'], true))
                                            <button type="button" wire:click="reactivar({{ $asignacion->id }})"
                                                class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-black text-white transition hover:bg-emerald-700">
                                                Reactivar
                                            </button>
                                        @endif

                                        @if (auth()->user()?->is_admin &&
                                                (int) ($asignacion->horarios_count ?? 0) === 0 &&
                                                (int) ($asignacion->calificaciones_count ?? 0) === 0 &&
                                                (int) ($asignacion->bitacora_calificaciones_count ?? 0) === 0)
                                            <button type="button"
                                                data-nombre="{{ $asignacion->materia?->materia ?? 'Materia' }}"
                                                data-contexto="{{ $asignacion->grupo?->grado?->nombre ?? 'Sin grado' }} · Grupo {{ $asignacion->grupo?->asignacionGrupo?->nombre ?? '—' }} · Generación {{ $asignacion->grupo?->generacion?->anio_ingreso ?? '—' }}-{{ $asignacion->grupo?->generacion?->anio_egreso ?? '—' }}{{ $asignacion->grupo?->semestre ? ' · ' . $asignacion->grupo->semestre->numero . '° semestre' : '' }}"
                                                x-on:click="confirmarEliminar({{ $asignacion->id }}, $el.dataset.nombre, $el.dataset.contexto)"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-red-600 px-2.5 py-1.5 text-[11px] font-black text-white transition hover:-translate-y-0.5 hover:bg-red-700">
                                                <flux:icon.trash class="h-3.5 w-3.5" />
                                                Eliminar
                                            </button>
                                        @endif

                                        @if (auth()->user()?->is_admin &&
                                                ((int) ($asignacion->horarios_count ?? 0) > 0 ||
                                                    (int) ($asignacion->calificaciones_count ?? 0) > 0 ||
                                                    (int) ($asignacion->bitacora_calificaciones_count ?? 0) > 0) &&
                                                $asignacion->estado !== 'archivada')
                                            <button type="button"
                                                data-nombre="{{ $asignacion->materia?->materia ?? 'Materia' }}"
                                                data-contexto="{{ $asignacion->grupo?->grado?->nombre ?? 'Sin grado' }} · Grupo {{ $asignacion->grupo?->asignacionGrupo?->nombre ?? '—' }} · Generación {{ $asignacion->grupo?->generacion?->anio_ingreso ?? '—' }}-{{ $asignacion->grupo?->generacion?->anio_egreso ?? '—' }}{{ $asignacion->grupo?->semestre ? ' · ' . $asignacion->grupo->semestre->numero . '° semestre' : '' }}"
                                                x-on:click="confirmarArchivar({{ $asignacion->id }}, $el.dataset.nombre, $el.dataset.contexto)"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-black text-rose-700 transition hover:-translate-y-0.5 hover:bg-rose-100">
                                                <flux:icon.archive-box class="h-3.5 w-3.5" />
                                                Archivar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    Mostrando
                    <span
                        class="font-black text-slate-700 dark:text-slate-200">{{ $this->asignacionesFiltradas->firstItem() }}</span>
                    a
                    <span
                        class="font-black text-slate-700 dark:text-slate-200">{{ $this->asignacionesFiltradas->lastItem() }}</span>
                    de
                    <span
                        class="font-black text-slate-700 dark:text-slate-200">{{ $this->asignacionesFiltradas->total() }}</span>
                    materias.
                </p>

                @if ($this->asignacionesFiltradas->hasPages())
                    <div class="min-w-0">
                        {{ $this->asignacionesFiltradas->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        @endif
    </section>


    {{-- Modal profesional de edición --}}
    <div x-data="{ abierto: $wire.entangle('modalEditarAbierto') }" x-show="abierto" x-cloak
        x-on:keydown.escape.window="if (abierto) $wire.cerrarModalEdicion()"
        x-effect="document.body.classList.toggle('overflow-hidden', abierto)"
        class="fixed inset-0 z-[9997] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true"
        aria-labelledby="titulo-modal-editar-carga">

        <div x-show="abierto" x-transition.opacity class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm"
            x-on:click="$wire.cerrarModalEdicion()"></div>

        <div x-show="abierto" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-[2rem] border border-white/20 bg-white shadow-2xl shadow-blue-950/30 dark:bg-slate-950">

            {{-- Loader al guardar la edición --}}
            <div wire:loading.flex wire:target="actualizarMateria"
                class="absolute inset-0 z-30 hidden items-center justify-center rounded-[2rem] bg-white/90 p-5 backdrop-blur-sm dark:bg-slate-950/90">
                <div class="text-center">
                    <div class="relative mx-auto mb-4 h-16 w-16">
                        <div
                            class="absolute inset-0 animate-spin rounded-full border-4 border-blue-100 border-r-[#88AC2E] border-t-[#006492] dark:border-slate-700">
                        </div>
                        <div
                            class="absolute inset-[10px] flex items-center justify-center rounded-2xl bg-gradient-to-br from-[#006492] to-[#88AC2E] text-white shadow-lg">
                            <flux:icon.arrow-path class="h-6 w-6" />
                        </div>
                    </div>
                    <p class="font-black text-slate-900 dark:text-white">Guardando cambios</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Actualizando la carga académica sin alterar otros ciclos…
                    </p>
                </div>
            </div>

            <div class="h-1.5 bg-gradient-to-r from-[#006492] via-sky-500 to-[#88AC2E]"></div>

            <div
                class="border-b border-slate-200 bg-gradient-to-r from-blue-50 via-white to-lime-50 px-5 py-5 dark:border-slate-800 dark:from-blue-950/30 dark:via-slate-950 dark:to-lime-950/20 sm:px-7">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-blue-500/20">
                            <flux:icon.pencil-square class="h-6 w-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 id="titulo-modal-editar-carga"
                                    class="text-xl font-black text-slate-900 dark:text-white">
                                    Editar carga académica
                                </h3>
                                <span
                                    class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                    ID {{ $editandoId ?: '—' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Corrige el contexto, la materia o el profesor. Los cambios se aplicarán únicamente al
                                ciclo
                                {{ $this->cicloSeleccionado?->inicio_anio ?? '—' }}-{{ $this->cicloSeleccionado?->fin_anio ?? '—' }}.
                            </p>
                        </div>
                    </div>

                    <button type="button" wire:click="cerrarModalEdicion" wire:loading.attr="disabled"
                        wire:target="actualizarMateria"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <flux:icon.x-mark class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="space-y-6 p-5 sm:p-7">
                <div
                    class="grid grid-cols-1 gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-900/40 dark:bg-blue-950/20 sm:grid-cols-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-300">
                            Nivel</p>
                        <p class="mt-1 text-sm font-black text-slate-800 dark:text-white">{{ $nivel?->nombre ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-300">
                            Ciclo</p>
                        <p class="mt-1 text-sm font-black text-slate-800 dark:text-white">
                            {{ $this->cicloSeleccionado?->inicio_anio ?? '—' }}-{{ $this->cicloSeleccionado?->fin_anio ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-300">
                            Alcance</p>
                        <p class="mt-1 text-sm font-black text-slate-800 dark:text-white">Solo esta carga</p>
                    </div>
                </div>

                @if ($edicionTieneHistorial)
                    <div
                        class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                            <flux:icon.shield-exclamation class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-black text-amber-900 dark:text-amber-100">Carga con historial protegido</p>
                            <p class="mt-1 text-sm leading-6 text-amber-800/80 dark:text-amber-200/80">
                                Ya existen horarios, calificaciones o auditoría. Para evitar inconsistencias, el grupo y
                                la materia
                                permanecen bloqueados; únicamente puedes actualizar el profesor responsable.
                            </p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <flux:field>
                            <flux:label>Grado, grupo y generación</flux:label>
                            <flux:select wire:model.live="editar_grupo_id" :disabled="$edicionTieneHistorial">
                                <flux:select.option value="">Selecciona un grupo</flux:select.option>
                                @foreach ($this->grupos as $grupo)
                                    <flux:select.option value="{{ $grupo->id }}">
                                        {{ $grupo->grado?->nombre ?? 'Sin grado' }} · Grupo
                                        {{ $grupo->asignacionGrupo?->nombre ?? '—' }} ·
                                        {{ $grupo->generacion?->anio_ingreso ?? '—' }}-{{ $grupo->generacion?->anio_egreso ?? '—' }}{{ $grupo->semestre ? ' · ' . $grupo->semestre->numero . '° semestre' : '' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="editar_grupo_id" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Materia</flux:label>
                        <flux:select wire:model="editar_materia_id"
                            :disabled="$edicionTieneHistorial || blank($editar_grupo_id)">
                            <flux:select.option value="">Selecciona una materia</flux:select.option>
                            @foreach ($this->materiasEdicionDisponibles as $materia)
                                <flux:select.option value="{{ $materia->id }}">
                                    {{ $materia->materia }}{{ $materia->clave ? ' · ' . $materia->clave : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="editar_materia_id" />
                    </flux:field>

                    <div class="relative">
                        <flux:field>
                            <flux:label>Profesor responsable</flux:label>
                            <flux:input wire:model.live.debounce.300ms="editarBuscarProfesor" icon="magnifying-glass"
                                placeholder="Puede quedar pendiente" autocomplete="off" />
                            <flux:error name="editar_profesor_id" />
                        </flux:field>

                        @if ($editarBuscarProfesor !== '' && blank($editar_profesor_id))
                            <div
                                class="absolute z-40 mt-2 max-h-60 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                @forelse ($this->profesoresEdicionFiltrados as $profesor)
                                    <button type="button"
                                        wire:click="seleccionarProfesorEdicion({{ $profesor['id'] }})"
                                        class="block w-full border-b border-slate-100 px-4 py-3 text-left text-sm font-bold text-slate-700 last:border-0 hover:bg-blue-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-blue-950/30">
                                        {{ $profesor['nombre'] }}
                                    </button>
                                @empty
                                    <p class="p-4 text-center text-sm text-slate-500">Sin coincidencias.</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                @if ($this->grupoEdicionSeleccionado)
                    <div
                        class="flex flex-wrap items-center gap-2 rounded-2xl border border-lime-200 bg-lime-50/80 px-4 py-3 text-xs font-bold text-slate-600 dark:border-lime-900/40 dark:bg-lime-950/20 dark:text-slate-300">
                        <span
                            class="rounded-full bg-white px-2.5 py-1 text-[#006492] shadow-sm dark:bg-slate-900 dark:text-blue-300">
                            {{ $this->grupoEdicionSeleccionado->grado?->nombre ?? 'Sin grado' }}
                        </span>
                        <span>Grupo {{ $this->grupoEdicionSeleccionado->asignacionGrupo?->nombre ?? '—' }}</span>
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <span>Generación
                            {{ $this->grupoEdicionSeleccionado->generacion?->anio_ingreso ?? '—' }}-{{ $this->grupoEdicionSeleccionado->generacion?->anio_egreso ?? '—' }}
                        </span>
                        @if ($this->grupoEdicionSeleccionado->semestre)
                            <span class="text-slate-300 dark:text-slate-600">•</span>
                            <span class="text-violet-700 dark:text-violet-300">
                                {{ $this->grupoEdicionSeleccionado->semestre->numero }}° semestre
                            </span>
                        @endif
                    </div>
                @endif

                <div
                    class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" wire:click="cerrarModalEdicion" wire:loading.attr="disabled"
                        wire:target="actualizarMateria"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        Cancelar
                    </button>

                    <button type="button" wire:click="actualizarMateria" wire:loading.attr="disabled"
                        wire:target="actualizarMateria"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#006492] to-[#88AC2E] px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 disabled:translate-y-0 disabled:cursor-wait disabled:opacity-60">
                        <flux:icon.check class="h-5 w-5" />
                        <span wire:loading.remove wire:target="actualizarMateria">Guardar cambios</span>
                        <span wire:loading wire:target="actualizarMateria">Actualizando…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
