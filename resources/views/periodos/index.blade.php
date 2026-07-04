<x-layouts.app :title="__('Administración de periodos')">
    <div class="mx-auto flex w-full max-w-[1600px] flex-1 flex-col gap-6">
        <section
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#006492] via-[#087cab] to-[#88AC2E] px-6 py-7 text-white shadow-xl shadow-sky-900/10 sm:px-8">
            <div class="absolute -right-14 -top-20 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-lime-300/20 blur-3xl"></div>
            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-sky-100">Control académico</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Administración de periodos</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-white/80 sm:text-base">
                        Configura los periodos de evaluación, sus fechas y la relación académica de cada nivel
                        educativo.
                    </p>
                </div>
                <div
                    class="flex items-center gap-3 rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                    <flux:icon.shield-check class="h-6 w-6" />
                    <div>
                        <p class="text-xs text-white/70">Módulo institucional</p>
                        <p class="font-bold">Moctezuma Básica</p>
                    </div>
                </div>
            </div>
        </section>

        <livewire:periodo.crear-periodo />
        <livewire:periodo.mostrar-periodos />
    </div>
</x-layouts.app>
