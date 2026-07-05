<div x-data="{
    eliminar(id) {
        Swal.fire({
            title: '¿Eliminar la configuración?',
            text: 'Se eliminará el ajuste manual y se aplicará la regla predeterminada del nivel.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar ajuste'
        }).then((resultado) => resultado.isConfirmed && @this.call('eliminarConfiguracionPromedio', id));
    }
}" class="space-y-5">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20 lg:col-span-2">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#006492] text-white">
                    <flux:icon.academic-cap class="h-5 w-5" />
                </div>
                <div>
                    <p class="font-black text-slate-900 dark:text-white">Regla de cálculo</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Cuando existe una configuración en <strong>materia_promediar</strong>, se utiliza ese número.
                        En bachillerato, si no existe, el sistema cuenta automáticamente únicamente las materias con
                        <strong>calificable = 1</strong> del grado y semestre. Los demás niveles conservan su regla actual.
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
            <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                Nivel configurado
            </p>
            <p class="mt-1 text-xl font-black text-emerald-900 dark:text-emerald-100">
                {{ $nombre_nivel ?: 'Nivel' }}
            </p>
            <p class="mt-1 text-xs font-semibold text-emerald-700/80 dark:text-emerald-300/80">
                La configuración se guarda por grado{{ $this->esBachillerato ? ' y semestre' : '' }}.
            </p>
        </div>
    </div>

    <form wire:submit.prevent="guardarMateriasPromediar"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 class="font-black text-slate-900 dark:text-white">Definir número de materias</h4>
                    <p class="text-xs text-slate-500">Selecciona el contexto académico y confirma el número que se utilizará.</p>
                </div>

                @if ($promediar_grado_id && (!$this->esBachillerato || $promediar_semestre_id))
                    <span @class([
                        'w-fit rounded-full px-3 py-1.5 text-xs font-black',
                        'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300' => $this->fuenteNumeroMaterias === 'configurada',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' => $this->fuenteNumeroMaterias === 'automatica',
                        'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' => $this->fuenteNumeroMaterias === 'pendiente',
                    ])>
                        @if ($this->fuenteNumeroMaterias === 'configurada')
                            Configuración manual activa
                        @elseif ($this->fuenteNumeroMaterias === 'automatica')
                            Cálculo automático activo
                        @else
                            Configuración pendiente
                        @endif
                    </span>
                @endif
            </div>
        </div>

        <div class="space-y-5 p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }} xl:items-end">
                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="promediar_grado_id">
                        <flux:select.option value="">Selecciona un grado</flux:select.option>
                        @foreach ($promediar_grados as $item)
                            <flux:select.option value="{{ $item['id'] }}">{{ $item['nombre'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="promediar_grado_id" />
                </flux:field>

                @if ($this->esBachillerato)
                    <flux:field>
                        <flux:label>Semestre</flux:label>
                        <flux:select wire:model.live="promediar_semestre_id" :disabled="blank($promediar_grado_id)">
                            <flux:select.option value="">Selecciona un semestre</flux:select.option>
                            @foreach ($promediar_semestres as $item)
                                <flux:select.option value="{{ $item['id'] }}">
                                    {{ $item['numero'] ?? ($item['semestre'] ?? ($item['nombre'] ?? 'Semestre')) }}{{ filled($item['numero'] ?? null) ? '° semestre' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="promediar_semestre_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Número de materias</flux:label>
                    <flux:input type="number" min="1" max="100" wire:model="promediar_numero_materias"
                        placeholder="Ejemplo: 9" :disabled="blank($promediar_grado_id) || ($this->esBachillerato && blank($promediar_semestre_id))" />
                    <flux:error name="promediar_numero_materias" />
                </flux:field>

                <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
                    @if ($this->configuracionSeleccionada)
                        <button type="button" wire:click="restablecerAutomatico"
                            wire:confirm="¿Eliminar esta configuración manual?"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            {{ $this->esBachillerato ? 'Usar automático' : 'Eliminar ajuste' }}
                        </button>
                    @endif

                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#006492] px-5 py-2 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:bg-[#005474] disabled:opacity-60">
                        <flux:icon.check-circle class="h-4 w-4" />
                        <span wire:loading.remove wire:target="guardarMateriasPromediar">Guardar configuración</span>
                        <span wire:loading wire:target="guardarMateriasPromediar">Guardando…</span>
                    </button>
                </div>
            </div>

            @if ($promediar_grado_id && (!$this->esBachillerato || $promediar_semestre_id))
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Calificables detectadas</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $this->materiasCalificablesDetectadas }}</p>
                        <p class="mt-1 text-xs text-slate-500">Materias con calificable = 1.</p>
                    </div>

                    <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/20">
                        <p class="text-[11px] font-black uppercase tracking-wide text-violet-700 dark:text-violet-300">Número efectivo</p>
                        <p class="mt-1 text-2xl font-black text-violet-900 dark:text-violet-100">{{ $this->numeroMateriasEfectivo }}</p>
                        <p class="mt-1 text-xs text-violet-700/80 dark:text-violet-300/80">
                            @if ($this->fuenteNumeroMaterias === 'configurada')
                                Tomado de materia_promediar.
                            @elseif ($this->fuenteNumeroMaterias === 'automatica')
                                Conteo automático.
                            @else
                                Requiere configuración.
                            @endif
                        </p>
                    </div>

                    <div @class([
                        'rounded-2xl border p-4',
                        'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20' => !$this->configuracionSeleccionada || $this->numeroMateriasEfectivo <= $this->materiasCalificablesDetectadas,
                        'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/20' => $this->configuracionSeleccionada && $this->numeroMateriasEfectivo > $this->materiasCalificablesDetectadas,
                    ])>
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Validación</p>
                        @if ($this->configuracionSeleccionada && $this->numeroMateriasEfectivo > $this->materiasCalificablesDetectadas)
                            <p class="mt-1 text-sm font-bold text-amber-800 dark:text-amber-200">
                                El número configurado supera las materias calificables detectadas.
                            </p>
                        @else
                            <p class="mt-1 text-sm font-bold text-emerald-800 dark:text-emerald-200">
                                La configuración es consistente con el catálogo actual.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </form>

    @php($cobertura = $this->coberturaPromedio)

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="font-black text-slate-900 dark:text-white">Cobertura de promedios</h4>
                <p class="text-xs text-slate-500">Muestra el número que realmente utilizará cada grado y semestre.</p>
            </div>
            <span class="w-fit rounded-full bg-slate-200 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ $cobertura->count() }} contexto(s)
            </span>
        </div>

        @if ($cobertura->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">No hay grados o semestres con materias registradas.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-100 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Grado</th>
                            @if ($this->esBachillerato)
                                <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Semestre</th>
                            @endif
                            <th class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">Calificables</th>
                            <th class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">Número efectivo</th>
                            <th class="px-4 py-3 text-center text-xs font-black uppercase tracking-wide text-slate-500">Origen</th>
                            <th class="px-4 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($cobertura as $fila)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/60">
                                <td class="px-4 py-3 font-bold text-slate-800 dark:text-slate-200">{{ $fila['grado'] }}</td>
                                @if ($this->esBachillerato)
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $fila['semestre'] }}</td>
                                @endif
                                <td class="px-4 py-3 text-center font-black text-slate-700 dark:text-slate-200">{{ $fila['detectadas'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex min-w-9 justify-center rounded-full bg-violet-100 px-2.5 py-1 text-xs font-black text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                        {{ $fila['efectivas'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[11px] font-black',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' => $fila['fuente'] === 'Configurada',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' => $fila['fuente'] === 'Automática',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' => $fila['fuente'] === 'Pendiente',
                                    ])>{{ $fila['fuente'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($fila['configuracion_id'])
                                        <button type="button" @click="eliminar({{ $fila['configuracion_id'] }})"
                                            class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-300">
                                            {{ $this->esBachillerato ? 'Automático' : 'Eliminar ajuste' }}
                                        </button>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">Sin ajuste manual</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
