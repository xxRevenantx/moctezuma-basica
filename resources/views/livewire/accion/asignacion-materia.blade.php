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
                        Las nuevas cargas quedan como borrador. Se pueden preparar y revisar, pero no participan en procesos operativos hasta confirmarlas.
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

                @if ($this->cicloSeleccionadoSinCargas && $this->cargasOrigenSeleccionado > 0)
                    <div class="xl:max-w-xl rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/20">
                        <p class="text-xs font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">Ciclo sin preparar</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            Este ciclo todavía no tiene cargas. Se detectó automáticamente el ciclo anterior con
                            <strong>{{ $this->cargasOrigenSeleccionado }}</strong> carga(s) confirmada(s). Usa <strong>Preparar cargas</strong> y aparecerán como borrador para revisión.
                        </p>
                    </div>
                @endif

                <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 xl:max-w-3xl xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Ciclo origen</flux:label>
                        <flux:select wire:model.live="ciclo_origen_id"
                            wire:key="ciclo-origen-{{ $ciclo_escolar_id }}"
                            :disabled="$this->ciclosOrigenDisponibles->isEmpty()">
                            @if ($this->ciclosOrigenDisponibles->isEmpty())
                                <flux:select.option value="">Sin ciclo anterior disponible</flux:select.option>
                            @else
                                @foreach ($this->ciclosOrigenDisponibles as $ciclo)
                                    <flux:select.option value="{{ $ciclo->id }}">
                                        {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                                    </flux:select.option>
                                @endforeach
                            @endif
                        </flux:select>
                        @if ($this->cargasOrigenSeleccionado > 0)
                            <p class="mt-1 text-xs font-bold text-indigo-600 dark:text-indigo-300">
                                {{ $this->cargasOrigenSeleccionado }} carga(s) confirmada(s) disponibles para copiar.
                            </p>
                        @endif
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
                        @disabled(!$ciclo_origen_id || $this->cargasOrigenSeleccionado === 0)
                        wire:confirm="Se crearán cargas nuevas EN BORRADOR para este nivel y ciclo. El ciclo origen no se modificará. Después deberás revisar y confirmar las cargas. ¿Continuar?"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:shadow-none dark:disabled:bg-slate-800 dark:disabled:text-slate-500">
                        <flux:icon.document-duplicate class="h-5 w-5" />
                        <span wire:loading.remove wire:target="copiarDesdeCiclo">
                            {{ $this->cargasOrigenSeleccionado > 0 ? 'Preparar ' . $this->cargasOrigenSeleccionado . ' cargas' : 'Preparar ciclo' }}
                        </span>
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

                <div class="xl:col-span-3">
                    <flux:field>
                        <flux:label>Profesor responsable</flux:label>
                        <flux:select wire:model="profesor_id" :disabled="$this->profesores->isEmpty()">
                            <flux:select.option value="">Puede quedar pendiente</flux:select.option>
                            @foreach ($this->profesores as $profesor)
                                <flux:select.option value="{{ $profesor['id'] }}">
                                    {{ $profesor['nombre'] }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="profesor_id" />
                    </flux:field>

                    @if ($this->profesores->isEmpty())
                        <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">
                            Los docentes aparecerán cuando la plantilla de este ciclo y nivel esté publicada.
                        </p>
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

                @if (auth()->user()?->is_admin)
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="abrirHistorialReasignaciones"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            <flux:icon.clock class="h-4 w-4" />
                            Historial de cambios
                        </button>
                        <button type="button" wire:click="abrirReasignacionMasiva"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#006492] to-[#88AC2E] px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5">
                            <flux:icon.users class="h-4 w-4" />
                            Reasignación masiva
                            @if (count($seleccionados) > 0)
                                <span class="rounded-full bg-white/20 px-2 py-0.5">{{ count($seleccionados) }}</span>
                            @endif
                        </button>
                        <button type="button" wire:click="confirmarTodas"
                            @disabled($this->totalBorradores === 0)
                            @if ($this->totalBorradores > 0)
                                wire:confirm="¿Confirmar las {{ $this->totalBorradores }} carga(s) en borrador de este nivel? Desde ese momento estarán disponibles para los procesos académicos operativos."
                            @endif
                            class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black shadow-sm transition {{ $this->totalBorradores > 0 ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500' }}">
                            <flux:icon.check-circle class="h-4 w-4" />
                            Confirmar borradores ({{ $this->totalBorradores }})
                        </button>
                    </div>
                @endif
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-950">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Resultados</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">
                        {{ $this->resumenCargas['total'] }}</p>
                </div>
                <button type="button" wire:click="filtrarEstado('activa')"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900/50 dark:bg-emerald-950/20 {{ $filtro_estado === 'activa' ? 'ring-2 ring-emerald-400 ring-offset-2 dark:ring-offset-slate-950' : '' }}">
                    <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                        Activas</p>
                    <p class="mt-1 text-2xl font-black text-emerald-800 dark:text-emerald-200">
                        {{ $this->resumenCargas['activas'] }}</p>
                    <p class="mt-1 text-[10px] font-bold text-emerald-700/70 dark:text-emerald-300/70">Clic para filtrar</p>
                </button>
                <button type="button" wire:click="filtrarEstado('borrador')"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md dark:border-amber-900/50 dark:bg-amber-950/20 {{ $filtro_estado === 'borrador' ? 'ring-2 ring-amber-400 ring-offset-2 dark:ring-offset-slate-950' : '' }}">
                    <p class="text-[11px] font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">
                        Borradores</p>
                    <p class="mt-1 text-2xl font-black text-amber-800 dark:text-amber-200">
                        {{ $this->resumenCargas['borradores'] }}</p>
                    <p class="mt-1 text-[10px] font-bold text-amber-700/70 dark:text-amber-300/70">Pendientes de confirmar</p>
                </button>
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

        @if (auth()->user()?->is_admin && count($seleccionados) > 0)
            <div class="flex flex-col gap-3 border-b border-blue-200 bg-blue-50/80 px-5 py-4 dark:border-blue-900/50 dark:bg-blue-950/20 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex flex-wrap items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-100">
                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-[#006492] px-2 text-xs font-black text-white">{{ count($seleccionados) }}</span>
                    <span>materia(s) seleccionada(s)</span>
                    <span class="text-xs font-semibold text-blue-700/70 dark:text-blue-200/70">Los recesos y registros auxiliares se excluyen automáticamente.</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if (count($seleccionados) < $this->totalSeleccionablesFiltrados)
                        <button type="button" wire:click="seleccionarTodosFiltrados"
                            class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-slate-900 dark:text-blue-300">
                            Seleccionar las {{ $this->totalSeleccionablesFiltrados }} materias filtradas
                        </button>
                    @endif
                    <button type="button" wire:click="limpiarSeleccionTabla"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                        Limpiar selección
                    </button>
                    <button type="button" wire:click="abrirReasignacionMasiva"
                        class="rounded-xl bg-[#006492] px-4 py-2 text-xs font-black text-white shadow-sm transition hover:bg-[#00557b]">
                        Cambiar profesor
                    </button>
                </div>
            </div>
        @endif

        @if ($this->asignacionesFiltradas->count() === 0)
            <div class="p-12 text-center">
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 dark:bg-slate-900">
                    <flux:icon.inbox class="h-8 w-8 text-slate-400" />
                </div>
                <p class="mt-4 font-black text-slate-800 dark:text-white">No hay cargas que coincidan.</p>
                @if ($this->tieneFiltrosActivos)
                    <p class="mt-1 text-sm text-slate-500">Ajusta o limpia los filtros para ampliar la consulta.</p>
                @elseif ($this->cicloSeleccionadoSinCargas && $this->cargasOrigenSeleccionado > 0)
                    <p class="mt-1 text-sm text-slate-500">
                        El ciclo seleccionado todavía no ha sido preparado. Arriba ya quedó seleccionado el ciclo anterior con
                        {{ $this->cargasOrigenSeleccionado }} carga(s); pulsa <strong>Preparar cargas</strong> para crearlas como borrador.
                    </p>
                @else
                    <p class="mt-1 text-sm text-slate-500">Puedes capturarlas manualmente o prepararlas desde un ciclo anterior disponible.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-100 dark:bg-slate-900">
                        <tr>
                            @if (auth()->user()?->is_admin)
                                <th class="w-12 px-4 py-3 text-center">
                                    <input type="checkbox" aria-label="Seleccionar página"
                                        @checked($this->paginaSeleccionada)
                                        wire:click="alternarSeleccionPagina"
                                        class="h-4 w-4 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                                </th>
                            @endif
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
                                @class([
                                    'align-top transition hover:bg-slate-50 dark:hover:bg-slate-900/60',
                                    'bg-blue-50/50 dark:bg-blue-950/10' => in_array((int) $asignacion->id, array_map('intval', $seleccionados), true),
                                ])>
                                @if (auth()->user()?->is_admin)
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" value="{{ $asignacion->id }}"
                                            wire:model.live="seleccionados"
                                            @disabled((bool) $asignacion->materia?->receso)
                                            aria-label="Seleccionar {{ $asignacion->materia?->materia ?? 'materia' }}"
                                            class="h-4 w-4 rounded border-slate-300 text-[#006492] focus:ring-[#006492] disabled:cursor-not-allowed disabled:opacity-30">
                                    </td>
                                @endif
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
                                        @if ($asignacion->esEditableEstructuralmente())
                                            <button type="button" wire:click="editar({{ $asignacion->id }})"
                                                wire:loading.attr="disabled" wire:target="editar"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-black text-blue-700 transition hover:-translate-y-0.5 hover:bg-blue-100 disabled:cursor-wait disabled:opacity-60">
                                                <flux:icon.pencil-square class="h-3.5 w-3.5" />
                                                Editar
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[11px] font-black text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-500"
                                                title="Reactiva la carga para editar su estructura">
                                                <flux:icon.lock-closed class="h-3.5 w-3.5" />
                                                Protegida
                                            </span>
                                        @endif

                                        @if (auth()->user()?->is_admin && $asignacion->estado === 'borrador')
                                            <button type="button" wire:click="confirmar({{ $asignacion->id }})"
                                                wire:confirm="¿Confirmar esta carga? Dejará de ser borrador y quedará disponible para los procesos académicos operativos."
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

                    <flux:field>
                        <flux:label>Profesor responsable</flux:label>
                        <flux:select wire:model="editar_profesor_id" :disabled="$this->profesores->isEmpty()">
                            <flux:select.option value="">Puede quedar pendiente</flux:select.option>
                            @foreach ($this->profesores as $profesor)
                                <flux:select.option value="{{ $profesor['id'] }}">
                                    {{ $profesor['nombre'] }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="editar_profesor_id" />

                        @if ($this->profesores->isEmpty())
                            <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                Los docentes aparecerán cuando la plantilla de este ciclo y nivel esté publicada.
                            </p>
                        @endif
                    </flux:field>
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

    {{-- Reasignación masiva de profesores --}}
    <div x-data="{ abierto: $wire.entangle('modalReasignacionAbierto') }" x-show="abierto" x-cloak
        x-on:keydown.escape.window="if (abierto) $wire.cerrarReasignacionMasiva()"
        x-effect="document.body.classList.toggle('overflow-hidden', abierto)"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-2 sm:p-5" role="dialog" aria-modal="true"
        aria-labelledby="titulo-reasignacion-masiva">
        <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" wire:click="cerrarReasignacionMasiva"></div>

        <div class="relative flex max-h-[95vh] w-full max-w-7xl flex-col overflow-hidden rounded-[2rem] border border-white/60 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-950">
            <div class="h-1.5 shrink-0 bg-gradient-to-r from-[#006492] via-blue-500 to-[#88AC2E]"></div>
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-blue-50 via-white to-lime-50 px-5 py-4 dark:border-slate-800 dark:from-blue-950/30 dark:via-slate-950 dark:to-lime-950/20 sm:px-7">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-blue-500/20">
                        <flux:icon.users class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 id="titulo-reasignacion-masiva" class="text-xl font-black text-slate-900 dark:text-white">Reasignación masiva de docentes</h2>
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                {{ $nivel?->nombre }} · {{ $this->cicloSeleccionado?->inicio_anio }}-{{ $this->cicloSeleccionado?->fin_anio }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Cambia el profesor de varias materias sin alterar calificaciones ni las versiones publicadas del horario.
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="cerrarReasignacionMasiva"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-rose-50 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-900">
                    <flux:icon.x-mark class="h-5 w-5" />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                @if ($reasignacion_paso === 'seleccion')
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                            <div class="xl:col-span-4">
                                <p class="mb-2 text-xs font-black uppercase tracking-wide text-slate-500">Forma de selección</p>
                                <div class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5 dark:bg-slate-900">
                                    <label @class([
                                        'cursor-pointer rounded-xl px-3 py-3 text-center text-xs font-black transition',
                                        'bg-white text-[#006492] shadow-sm dark:bg-slate-800 dark:text-blue-300' => $reasignacion_modo === 'seleccion',
                                        'text-slate-500' => $reasignacion_modo !== 'seleccion',
                                    ])>
                                        <input type="radio" value="seleccion" wire:model.live="reasignacion_modo" class="sr-only">
                                        Selección de tabla
                                        <span class="mt-1 block text-[10px] font-bold opacity-70">{{ count($reasignacion_ids_base) }} registro(s)</span>
                                    </label>
                                    <label @class([
                                        'cursor-pointer rounded-xl px-3 py-3 text-center text-xs font-black transition',
                                        'bg-white text-[#006492] shadow-sm dark:bg-slate-800 dark:text-blue-300' => $reasignacion_modo === 'profesor',
                                        'text-slate-500' => $reasignacion_modo !== 'profesor',
                                    ])>
                                        <input type="radio" value="profesor" wire:model.live="reasignacion_modo" class="sr-only">
                                        Profesor actual
                                        <span class="mt-1 block text-[10px] font-bold opacity-70">Incluye “sin docente”</span>
                                    </label>
                                </div>
                            </div>

                            <div class="xl:col-span-4">
                                <flux:field>
                                    <flux:label>Profesor actual</flux:label>
                                    <flux:select wire:model.live="reasignacion_origen" :disabled="$reasignacion_modo !== 'profesor'">
                                        <flux:select.option value="">Selecciona el origen</flux:select.option>
                                        @foreach ($this->profesoresOrigenReasignacion as $profesorOrigen)
                                            <flux:select.option value="{{ $profesorOrigen['valor'] }}">
                                                {{ $profesorOrigen['nombre'] }} · {{ $profesorOrigen['total'] }} materia(s)
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            </div>

                            <div class="xl:col-span-4">
                                <flux:field>
                                    <flux:label>Nuevo profesor</flux:label>
                                    <flux:select wire:model="reasignacion_destino_id" :disabled="$this->profesores->isEmpty()">
                                        <flux:select.option value="">Selecciona el profesor sustituto</flux:select.option>
                                        @foreach ($this->profesores as $profesor)
                                            <flux:select.option value="{{ $profesor['id'] }}">{{ $profesor['nombre'] }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="reasignacion_destino_id" />
                                    @if ($this->profesores->isEmpty())
                                        <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                            Publica la plantilla de este ciclo y nivel para habilitar los docentes destino.
                                        </p>
                                    @endif
                                </flux:field>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-black text-slate-900 dark:text-white">Filtros de la reasignación</p>
                                    <p class="text-xs text-slate-500">El alcance siempre queda limitado al ciclo y nivel mostrados arriba.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                        <input type="checkbox" wire:model.live="reasignacion_incluir_cerradas" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                        Incluir cerradas
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-black text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                                        <input type="checkbox" wire:model.live="reasignacion_incluir_archivadas" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                        Incluir archivadas
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                                <div class="md:col-span-2 xl:col-span-2">
                                    <flux:field>
                                        <flux:label>Buscar materia</flux:label>
                                        <flux:input wire:model.live.debounce.300ms="reasignacion_buscar" icon="magnifying-glass" placeholder="Materia, clave, grado o grupo" />
                                    </flux:field>
                                </div>
                                <flux:field>
                                    <flux:label>Generación</flux:label>
                                    <flux:select wire:model.live="reasignacion_generacion">
                                        <flux:select.option value="">Todas</flux:select.option>
                                        @foreach ($this->reasignacionGeneraciones as $generacion)
                                            <flux:select.option value="{{ $generacion->id }}">{{ $generacion->anio_ingreso }}-{{ $generacion->anio_egreso }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Estado</flux:label>
                                    <flux:select wire:model.live="reasignacion_estado">
                                        <flux:select.option value="">Todos permitidos</flux:select.option>
                                        <flux:select.option value="borrador">Borrador</flux:select.option>
                                        <flux:select.option value="activa">Activa</flux:select.option>
                                        @if ($reasignacion_incluir_cerradas)
                                            <flux:select.option value="cerrada">Cerrada</flux:select.option>
                                        @endif
                                        @if ($reasignacion_incluir_archivadas)
                                            <flux:select.option value="archivada">Archivada</flux:select.option>
                                        @endif
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Grado</flux:label>
                                    <flux:select wire:model.live="reasignacion_grado">
                                        <flux:select.option value="">Todos</flux:select.option>
                                        @foreach ($this->reasignacionGrados as $grado)
                                            <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                @if ($this->esBachillerato)
                                    <flux:field>
                                        <flux:label>Semestre</flux:label>
                                        <flux:select wire:model.live="reasignacion_semestre">
                                            <flux:select.option value="">Todos</flux:select.option>
                                            @foreach ($this->reasignacionSemestres as $semestre)
                                                <flux:select.option value="{{ $semestre->id }}">{{ $semestre->numero }}°</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                @endif
                                <flux:field>
                                    <flux:label>Grupo</flux:label>
                                    <flux:select wire:model.live="reasignacion_grupo">
                                        <flux:select.option value="">Todos</flux:select.option>
                                        @foreach ($this->reasignacionGrupos as $grupo)
                                            <flux:select.option value="{{ $grupo->id }}">{{ $grupo->grado?->nombre }} · {{ $grupo->asignacionGrupo?->nombre }}{{ $grupo->semestre ? ' · ' . $grupo->semestre->numero . '°' : '' }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Horario</flux:label>
                                    <flux:select wire:model.live="reasignacion_horario">
                                        <flux:select.option value="">Todos</flux:select.option>
                                        <flux:select.option value="con">Con horario</flux:select.option>
                                        <flux:select.option value="sin">Sin horario</flux:select.option>
                                    </flux:select>
                                </flux:field>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                            <div class="flex flex-col gap-3 border-b border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    <span class="font-black text-[#006492]">{{ $this->totalCandidatosReasignacion }}</span> materia(s) encontradas ·
                                    <span class="font-black text-[#88AC2E]">{{ count($reasignacion_seleccionados) }}</span> seleccionadas
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="seleccionarTodosCandidatosReasignacion"
                                        class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                                        Seleccionar todos los resultados
                                    </button>
                                    <button type="button" wire:click="limpiarSeleccionReasignacion"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        Quitar selección
                                    </button>
                                </div>
                            </div>

                            @if ($this->candidatosReasignacion->isEmpty())
                                <div class="p-10 text-center text-sm font-semibold text-slate-500">
                                    {{ $reasignacion_modo === 'profesor' && blank($reasignacion_origen) ? 'Selecciona un profesor actual para mostrar sus materias.' : 'No hay materias que coincidan con estos filtros.' }}
                                </div>
                            @else
                                <div class="max-h-72 overflow-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                        <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-slate-900">
                                            <tr>
                                                <th class="w-12 px-4 py-3"></th>
                                                <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Materia</th>
                                                <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Contexto</th>
                                                <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Profesor actual</th>
                                                <th class="px-4 py-3 text-center text-xs font-black uppercase text-slate-500">Bloques</th>
                                                <th class="px-4 py-3 text-center text-xs font-black uppercase text-slate-500">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            @foreach ($this->candidatosReasignacion as $candidato)
                                                <tr wire:key="candidato-reasignacion-{{ $candidato->id }}" class="hover:bg-slate-50 dark:hover:bg-slate-900/60">
                                                    <td class="px-4 py-3 text-center">
                                                        <input type="checkbox" value="{{ $candidato->id }}" wire:model.live="reasignacion_seleccionados"
                                                            class="h-4 w-4 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <p class="font-black text-slate-900 dark:text-white">{{ $candidato->materia?->materia }}</p>
                                                        @if ($candidato->materia?->clave)<p class="text-xs text-slate-500">{{ $candidato->materia->clave }}</p>@endif
                                                    </td>
                                                    <td class="px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                                        {{ $candidato->grupo?->grado?->nombre }} · Grupo {{ $candidato->grupo?->asignacionGrupo?->nombre }}
                                                        <span class="block text-slate-400">{{ $candidato->grupo?->generacion?->anio_ingreso }}-{{ $candidato->grupo?->generacion?->anio_egreso }}{{ $candidato->grupo?->semestre ? ' · ' . $candidato->grupo->semestre->numero . '° semestre' : '' }}</span>
                                                    </td>
                                                    <td class="px-4 py-3 text-xs font-bold text-slate-700 dark:text-slate-200">
                                                        {{ $candidato->profesor ? trim(($candidato->profesor->titulo ?? '') . ' ' . ($candidato->profesor->nombre ?? '') . ' ' . ($candidato->profesor->apellido_paterno ?? '') . ' ' . ($candidato->profesor->apellido_materno ?? '')) : 'Sin docente' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center text-xs font-black text-blue-700">{{ $candidato->horarios_count }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $candidato->estado }}</span>
                                                        @if (($candidato->calificaciones_count + $candidato->bitacora_calificaciones_count + $candidato->horarios_count) > 0)
                                                            <span class="mt-1 block text-[10px] font-bold text-amber-600">Historial protegido</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($this->totalCandidatosReasignacion > 250)
                                    <p class="border-t border-slate-200 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-800 dark:border-slate-800 dark:bg-amber-950/20 dark:text-amber-200">
                                        Se muestran los primeros 250 registros. “Seleccionar todos los resultados” incluye la consulta completa.
                                    </p>
                                @endif
                            @endif
                        </div>

                        <flux:error name="reasignacion_seleccionados" />
                    </div>
                @else
                    @php($resumenReasignacion = $reasignacion_preview['resumen'] ?? [])
                    <div class="space-y-5">
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
                            @foreach ([
                                ['Seleccionadas', $resumenReasignacion['seleccionadas'] ?? 0, 'text-slate-900'],
                                ['Aplicables', $resumenReasignacion['aplicables'] ?? 0, 'text-emerald-700'],
                                ['Grupos', $resumenReasignacion['grupos'] ?? 0, 'text-blue-700'],
                                ['Bloques', $resumenReasignacion['bloques'] ?? 0, 'text-indigo-700'],
                                ['Con historial', $resumenReasignacion['con_historial'] ?? 0, 'text-amber-700'],
                                ['Excluidas', $resumenReasignacion['excluidas'] ?? 0, 'text-slate-500'],
                                ['Conflictos', $resumenReasignacion['conflictos'] ?? 0, 'text-rose-700'],
                            ] as [$etiqueta, $valor, $color])
                                <div class="rounded-2xl border border-slate-200 bg-white p-3 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $etiqueta }}</p>
                                    <p class="mt-1 text-2xl font-black {{ $color }}">{{ $valor }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if (($resumenReasignacion['conflictos'] ?? 0) > 0)
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white">
                                        <flux:icon.exclamation-triangle class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-black text-rose-900 dark:text-rose-100">Se detectaron conflictos críticos</p>
                                        <p class="mt-1 text-sm text-rose-800/80 dark:text-rose-200/80">La operación queda bloqueada hasta autorizar la excepción administrativa y explicar el motivo.</p>
                                        <div class="mt-3 max-h-36 space-y-2 overflow-auto">
                                            @foreach (($reasignacion_preview['conflictos'] ?? []) as $conflicto)
                                                <div class="rounded-xl border border-rose-200 bg-white/80 px-3 py-2 text-xs text-rose-900 dark:border-rose-900 dark:bg-slate-950/40 dark:text-rose-100">
                                                    <span class="font-black">{{ $conflicto['titulo'] }}</span>
                                                    <span class="block mt-0.5">{{ $conflicto['detalle'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200">
                                <flux:icon.check-circle class="h-5 w-5" />
                                La disponibilidad y los traslapes fueron revisados. No se encontraron conflictos críticos.
                            </div>
                        @endif

                        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                            <div class="max-h-[40vh] overflow-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                    <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-slate-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Materia y grupo</th>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Profesor actual</th>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Profesor nuevo</th>
                                            <th class="px-4 py-3 text-center text-xs font-black uppercase text-slate-500">Bloques</th>
                                            <th class="px-4 py-3 text-center text-xs font-black uppercase text-slate-500">Estado</th>
                                            <th class="px-4 py-3 text-left text-xs font-black uppercase text-slate-500">Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @foreach (($reasignacion_preview['filas'] ?? []) as $fila)
                                            <tr @class(['bg-slate-50/70 dark:bg-slate-900/50' => !($fila['aplicable'] ?? false)])>
                                                <td class="px-4 py-3">
                                                    <p class="font-black text-slate-900 dark:text-white">{{ $fila['materia'] }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $fila['grupo'] }} · Generación {{ $fila['generacion'] }}{{ $fila['semestre'] ? ' · ' . $fila['semestre'] . '° semestre' : '' }}</p>
                                                </td>
                                                <td class="px-4 py-3 text-xs font-bold text-slate-600 dark:text-slate-300">{{ $fila['profesor_actual'] }}</td>
                                                <td class="px-4 py-3 text-xs font-black text-[#006492] dark:text-blue-300">{{ $fila['profesor_nuevo'] }}</td>
                                                <td class="px-4 py-3 text-center text-xs font-black">{{ $fila['bloques'] }}</td>
                                                <td class="px-4 py-3 text-center"><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $fila['estado'] }}</span></td>
                                                <td class="px-4 py-3 text-xs font-bold {{ count($fila['conflictos'] ?? []) > 0 ? 'text-rose-700' : (($fila['aplicable'] ?? false) ? 'text-emerald-700' : 'text-slate-500') }}">{{ $fila['resultado'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if (($resumenReasignacion['conflictos'] ?? 0) > 0)
                            <div class="grid grid-cols-1 gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20 lg:grid-cols-2">
                                <label class="flex cursor-pointer items-start gap-3 text-sm font-bold text-amber-900 dark:text-amber-100">
                                    <input type="checkbox" wire:model.live="reasignacion_autorizar_conflictos" class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <span>Autorizo administrativamente la reasignación aun con los conflictos mostrados.</span>
                                </label>
                                <flux:field>
                                    <flux:label>Motivo de la excepción</flux:label>
                                    <flux:textarea wire:model="reasignacion_motivo_conflictos" rows="3" placeholder="Explica por qué este traslape o restricción es válido..." />
                                    <flux:error name="reasignacion_autorizar_conflictos" />
                                    <flux:error name="reasignacion_motivo_conflictos" />
                                </flux:field>
                            </div>
                        @endif

                        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs font-semibold text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                            Se actualizarán la carga académica, los horarios operativos y las versiones en estado propuesta o borrador. Las versiones publicadas, sustituidas y archivadas conservarán al docente anterior como evidencia.
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <button type="button" wire:click="cerrarReasignacionMasiva" wire:loading.attr="disabled"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600 shadow-sm hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    Cancelar
                </button>
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    @if ($reasignacion_paso === 'preview')
                        <button type="button" wire:click="volverSeleccionReasignacion" wire:loading.attr="disabled"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            Volver a selección
                        </button>
                        <button type="button" wire:click="aplicarReasignacionMasiva" wire:loading.attr="disabled" wire:target="aplicarReasignacionMasiva"
                            @disabled(
                                ($reasignacion_preview['resumen']['aplicables'] ?? 0) === 0
                                || (($reasignacion_preview['resumen']['conflictos'] ?? 0) > 0
                                    && (! $reasignacion_autorizar_conflictos || mb_strlen(trim($reasignacion_motivo_conflictos)) < 10))
                            )
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#006492] to-[#88AC2E] px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <flux:icon.check class="h-5 w-5" />
                            <span wire:loading.remove wire:target="aplicarReasignacionMasiva">Aplicar reasignación</span>
                            <span wire:loading wire:target="aplicarReasignacionMasiva">Aplicando…</span>
                        </button>
                    @else
                        <button type="button" wire:click="previsualizarReasignacion" wire:loading.attr="disabled" wire:target="previsualizarReasignacion"
                            @disabled(count($reasignacion_seleccionados) === 0 || blank($reasignacion_destino_id))
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#006492] px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:bg-[#00557b] disabled:cursor-not-allowed disabled:opacity-50">
                            <flux:icon.eye class="h-5 w-5" />
                            <span wire:loading.remove wire:target="previsualizarReasignacion">Revisar antes de aplicar</span>
                            <span wire:loading wire:target="previsualizarReasignacion">Analizando…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Historial y reversión de lotes --}}
    <div x-data="{ abierto: $wire.entangle('modalHistorialReasignacionesAbierto') }" x-show="abierto" x-cloak
        x-on:keydown.escape.window="if (abierto) $wire.cerrarHistorialReasignaciones()"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" wire:click="cerrarHistorialReasignaciones"></div>
        <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] border border-white/60 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-950">
            <div class="h-1.5 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Historial de reasignaciones masivas</h2>
                    <p class="mt-1 text-sm text-slate-500">Los lotes pueden revertirse mientras las cargas y los horarios no tengan cambios posteriores incompatibles.</p>
                </div>
                <button type="button" wire:click="cerrarHistorialReasignaciones" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900"><flux:icon.x-mark class="h-5 w-5" /></button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">
                @if ($this->historialReasignaciones->isEmpty())
                    <div class="p-12 text-center text-sm font-semibold text-slate-500">Todavía no hay reasignaciones masivas en este ciclo y nivel.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($this->historialReasignaciones as $lote)
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span @class([
                                                'rounded-full px-2.5 py-1 text-[10px] font-black uppercase',
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' => $lote->estado === 'aplicada',
                                                'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' => $lote->estado === 'revertida',
                                                'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' => $lote->estado === 'reversion_parcial',
                                            ])>{{ str_replace('_', ' ', $lote->estado) }}</span>
                                            <span class="text-xs font-black text-slate-400">Lote {{ substr($lote->uuid, 0, 8) }}</span>
                                            <span class="text-xs font-semibold text-slate-500">{{ $lote->aplicado_at?->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <p class="mt-2 font-black text-slate-900 dark:text-white">
                                            {{ $lote->profesorOrigen
                                                ? trim(($lote->profesorOrigen->titulo ?? '') . ' ' . ($lote->profesorOrigen->nombre ?? '') . ' ' . ($lote->profesorOrigen->apellido_paterno ?? '') . ' ' . ($lote->profesorOrigen->apellido_materno ?? ''))
                                                : ($lote->metadata['profesor_origen_nombre'] ?? ($lote->modo === 'profesor' ? 'Sin docente asignado' : 'Selección manual')) }}
                                            <span class="mx-2 text-slate-300">→</span>
                                            {{ $lote->profesorDestino
                                                ? trim(($lote->profesorDestino->titulo ?? '') . ' ' . ($lote->profesorDestino->nombre ?? '') . ' ' . ($lote->profesorDestino->apellido_paterno ?? '') . ' ' . ($lote->profesorDestino->apellido_materno ?? ''))
                                                : ($lote->metadata['profesor_destino_nombre'] ?? 'Docente no disponible') }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-bold text-slate-500">
                                            <span>{{ $lote->total_asignaciones }} materias</span><span>•</span>
                                            <span>{{ $lote->total_horarios }} bloques</span><span>•</span>
                                            <span>{{ $lote->total_versiones }} bloques en propuestas</span>
                                            @if ($lote->total_conflictos > 0)<span>•</span><span class="text-rose-600">{{ $lote->total_conflictos }} conflictos autorizados</span>@endif
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        @if ($lote->estado === 'aplicada')
                                            <button type="button" wire:click="revertirReasignacion({{ $lote->id }})"
                                                wire:confirm="Se intentará restaurar cada profesor anterior. Los registros con cambios posteriores incompatibles se omitirán. ¿Continuar?"
                                                wire:loading.attr="disabled" wire:target="revertirReasignacion"
                                                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-black text-amber-800 transition hover:bg-amber-100 disabled:opacity-50 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                                <flux:icon.arrow-uturn-left class="h-4 w-4" />
                                                Revertir lote
                                            </button>
                                        @else
                                            <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500 dark:bg-slate-800">Sin acción disponible</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 text-right dark:border-slate-800 dark:bg-slate-900/70">
                <button type="button" wire:click="cerrarHistorialReasignaciones" class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-black text-white hover:bg-slate-900">Cerrar</button>
            </div>
        </div>
    </div>

</div>
