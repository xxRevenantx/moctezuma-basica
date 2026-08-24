<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Documentación"
        title="Credenciales institucionales"
        description="Unifica la emisión de credenciales de alumnos y profesores en un único espacio, conservando los filtros y formatos existentes." />

    <x-institucional.pestanas :tabs="['alumnos' => 'Alumnos', 'personal' => 'Profesores']" :active="$tab" />

    @if ($tab === 'alumnos')
        <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />
        <livewire:generales.credenciales
            :slug_nivel="$slug_nivel"
            :key="'credenciales-alumnos-global-' . $slug_nivel" />
    @else
        <section class="rounded-[1.6rem] border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-neutral-800 dark:bg-neutral-950/40 dark:text-slate-300">
            Las credenciales de profesores son institucionales y no requieren seleccionar un nivel de alumnos.
        </section>
        <livewire:profesor.credencial-profesor :key="'credenciales-personal-global'" />
    @endif
</div>
