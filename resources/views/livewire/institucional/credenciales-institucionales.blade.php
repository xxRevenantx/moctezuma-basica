<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Documentación"
        title="Credenciales institucionales"
        description="Unifica la emisión de credenciales de alumnos y profesores en un único espacio, y agrega herramientas documentales para el personal." />

    <x-institucional.pestanas :tabs="['alumnos' => 'Alumnos', 'personal' => 'Profesores']" :active="$tab" />

    @if ($tab === 'alumnos')
        <div x-data="{ alcance: 'todos' }" class="space-y-5">
            <div class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                <button type="button" @click="alcance = 'todos'" :aria-pressed="alcance === 'todos'"
                    :class="alcance === 'todos' ? 'bg-sky-700 text-white' : 'text-slate-500 hover:bg-slate-100'"
                    class="cursor-pointer rounded-xl px-4 py-3 text-sm font-bold">Todos los niveles</button>
                <button type="button" @click="alcance = 'nivel'" :aria-pressed="alcance === 'nivel'"
                    :class="alcance === 'nivel' ? 'bg-sky-700 text-white' : 'text-slate-500 hover:bg-slate-100'"
                    class="cursor-pointer rounded-xl px-4 py-3 text-sm font-bold">Descargas por nivel</button>
            </div>
            <div x-show="alcance === 'todos'">
                <livewire:institucional.credenciales-combinadas :key="'credenciales-combinadas'" />
            </div>
            <div x-show="alcance === 'nivel'" x-cloak class="space-y-5">
        <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />
        <livewire:generales.credenciales
            :slug_nivel="$slug_nivel"
            :key="'credenciales-alumnos-global-' . $slug_nivel" />
            </div>
        </div>
    @else
        <section class="space-y-4 rounded-[1.8rem] border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Las credenciales de profesores son institucionales y no requieren seleccionar un nivel de alumnos.
                        Además, ahora puedes administrar documentos personalizados del personal desde este mismo espacio.
                    </p>
                </div>
                <div class="inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                    <button type="button" wire:click="seleccionarTabPersonal('credenciales')"
                        class="rounded-xl px-4 py-2 text-sm font-bold transition {{ $tabPersonal === 'credenciales' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800' }}">
                        Credenciales
                    </button>
                    <button type="button" wire:click="seleccionarTabPersonal('documentos')"
                        class="rounded-xl px-4 py-2 text-sm font-bold transition {{ $tabPersonal === 'documentos' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800' }}">
                        Documentos
                    </button>
                </div>
            </div>
        </section>

        @if ($tabPersonal === 'credenciales')
            <livewire:profesor.credencial-profesor :key="'credenciales-personal-global'" />
        @else
            <livewire:profesor.documentos-profesor :key="'documentos-personal-global'" />
        @endif
    @endif
</div>
