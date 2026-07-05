<div class="space-y-6">
    <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] to-[#88AC2E]"></div>
        <div class="flex flex-col gap-4 p-5 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-white dark:text-slate-900"><flux:icon.cog-6-tooth class="h-6 w-6" /></div>
                <div><p class="text-xs font-black uppercase tracking-[0.18em] text-[#006492]">Media Superior</p><h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Configuración documental</h1><p class="mt-1 text-sm text-slate-500">Los datos del plantel y CCT se leen de Escuela y Niveles; aquí solo se completan campos oficiales faltantes y firmantes por vigencia.</p></div>
            </div>
            <a href="{{ route('media-superior.documentos.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><flux:icon.arrow-left class="h-4 w-4" /> Documentos oficiales</a>
        </div>
    </section>

    <form wire:submit="guardar" class="space-y-6">
        <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="border-b border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/70 sm:p-6"><h2 class="text-lg font-black text-slate-950 dark:text-white">Datos complementarios</h2><p class="mt-1 text-sm text-slate-500">La dirección, municipio, estado y CCT permanecen vinculados a la base de datos existente.</p></div>
            <div class="grid grid-cols-1 gap-5 p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-3">
                <flux:field><flux:label>Nombre oficial del plantel</flux:label><flux:input wire:model="nombre_plantel_oficial" placeholder="Vacío = usar Escuela.nombre" /><flux:error name="nombre_plantel_oficial" /></flux:field>
                <flux:field><flux:label>Número de acuerdo</flux:label><flux:input wire:model="numero_acuerdo" placeholder="Ej. SEG/0031/2021" /><flux:error name="numero_acuerdo" /></flux:field>
                <flux:field><flux:label>Modalidad</flux:label><flux:input wire:model="modalidad" /><flux:error name="modalidad" /></flux:field>
                <flux:field><flux:label>Turno</flux:label><flux:input wire:model="turno" /><flux:error name="turno" /></flux:field>
                <flux:field><flux:label>Localidad de expedición</flux:label><flux:input wire:model="localidad_expedicion" placeholder="Vacío = ciudad y estado de Escuela" /><flux:error name="localidad_expedicion" /></flux:field>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900"><input type="checkbox" wire:model="mostrar_materias_extra" class="rounded border-slate-300 text-[#006492]"><span><span class="block text-sm font-black text-slate-800 dark:text-slate-100">Mostrar materias extra</span><span class="block text-xs text-slate-500">Siempre separadas y sin promedio.</span></span></label>
                <flux:field><flux:label>Ruta logo SEG</flux:label><flux:input wire:model="logo_seg_path" /><flux:error name="logo_seg_path" /></flux:field>
                <flux:field><flux:label>Ruta logo plantel</flux:label><flux:input wire:model="logo_plantel_path" /><flux:error name="logo_plantel_path" /></flux:field>
            </div>
        </section>

        <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div class="border-b border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/70 sm:p-6"><h2 class="text-lg font-black text-slate-950 dark:text-white">Firmantes por vigencia</h2><p class="mt-1 text-sm text-slate-500">Selecciona personas ya registradas. Al guardar se conserva la vigencia para emitir documentos históricos correctamente.</p></div>
            <div class="space-y-5 p-5 sm:p-6">
                @foreach($roles as $rol => $cargoPredeterminado)
                    <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                        <div class="mb-4 flex items-center justify-between"><div><p class="font-black text-slate-950 dark:text-white">{{ $cargoPredeterminado }}</p><p class="text-xs text-slate-500">Rol técnico: {{ $rol }}</p></div></div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <flux:field><flux:label>Origen</flux:label><flux:select wire:model.live="firmantes.{{ $rol }}.tipo"><flux:select.option value="persona">Personal</flux:select.option><flux:select.option value="director">Directores</flux:select.option></flux:select></flux:field>
                            <flux:field class="xl:col-span-2"><flux:label>Firmante</flux:label><flux:select wire:model="firmantes.{{ $rol }}.id"><flux:select.option value="">Sin configurar</flux:select.option>@if(($firmantes[$rol]['tipo'] ?? 'persona') === 'director')@foreach($this->directores as $persona)<flux:select.option value="{{ $persona->id }}">{{ $persona->titulo }} {{ $persona->nombre }} {{ $persona->apellido_paterno }} {{ $persona->apellido_materno }}</flux:select.option>@endforeach @else @foreach($this->personas as $persona)<flux:select.option value="{{ $persona->id }}">{{ $persona->titulo }} {{ $persona->nombre }} {{ $persona->apellido_paterno }} {{ $persona->apellido_materno }}</flux:select.option>@endforeach @endif</flux:select><flux:error name="firmantes.{{ $rol }}.id" /></flux:field>
                            <flux:field class="xl:col-span-2"><flux:label>Cargo impreso</flux:label><flux:input wire:model="firmantes.{{ $rol }}.cargo" /><flux:error name="firmantes.{{ $rol }}.cargo" /></flux:field>
                            <flux:field><flux:label>Vigente desde</flux:label><flux:select wire:model="firmantes.{{ $rol }}.ciclo_desde_id"><flux:select.option value="">Sin límite</flux:select.option>@foreach($this->ciclos as $ciclo)<flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>@endforeach</flux:select><flux:error name="firmantes.{{ $rol }}.ciclo_desde_id" /></flux:field>
                            <flux:field><flux:label>Vigente hasta</flux:label><flux:select wire:model="firmantes.{{ $rol }}.ciclo_hasta_id"><flux:select.option value="">Sin límite</flux:select.option>@foreach($this->ciclos as $ciclo)<flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>@endforeach</flux:select><flux:error name="firmantes.{{ $rol }}.ciclo_hasta_id" /></flux:field>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-2xl bg-[#006492] px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-500/20 hover:bg-[#005474] disabled:opacity-60"><flux:icon.check class="h-5 w-5" /><span wire:loading.remove wire:target="guardar">Guardar configuración</span><span wire:loading wire:target="guardar">Guardando…</span></button></div>
    </form>
</div>
