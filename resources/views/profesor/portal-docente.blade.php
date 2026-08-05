<div class="min-h-[calc(100vh-4rem)] bg-zinc-50 px-4 py-6 dark:bg-zinc-950 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_auto] lg:items-center lg:p-8">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-[#006492]/10 px-3 py-1 text-xs font-bold text-[#006492] dark:bg-sky-400/10 dark:text-sky-300">
                        <span class="size-2 rounded-full bg-[#88AC2E]"></span>
                        Portal docente
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight text-zinc-950 dark:text-white">
                        Bienvenido, {{ auth()->user()->persona?->nombre ?? auth()->user()->name }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                        Consulta tu horario y captura únicamente las calificaciones o fichas correspondientes a tus asignaciones vigentes.
                    </p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-5 py-4 text-right dark:border-zinc-700 dark:bg-zinc-800/70">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-400">Ciclo actual</p>
                    <p class="mt-1 text-lg font-black text-zinc-900 dark:text-white">{{ $cicloActual?->ciclo ?? $cicloActual?->nombre ?? 'Sin configurar' }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('docente.horario') }}" wire:navigate
                class="group rounded-[24px] border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-[#006492]/40 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-[#006492] text-white shadow-lg shadow-sky-900/10">
                        <flux:icon.calendar-days class="size-6" />
                    </span>
                    <flux:icon.arrow-up-right class="size-5 text-zinc-300 transition group-hover:text-[#006492]" />
                </div>
                <h2 class="mt-5 text-lg font-black text-zinc-950 dark:text-white">Mi horario</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Consulta e imprime las clases y talleres que tienes asignados.</p>
            </a>

            @foreach ($niveles as $nivel)
                @php($esPreescolar = $nivel->slug === 'preescolar')
                <a href="{{ $esPreescolar ? route('docente.fichas') : route('docente.calificaciones', $nivel->slug) }}" wire:navigate
                    class="group rounded-[24px] border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-[#88AC2E]/60 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-[#88AC2E] text-white shadow-lg shadow-lime-900/10">
                            @if ($esPreescolar)
                                <flux:icon.document-text class="size-6" />
                            @else
                                <flux:icon.pencil-square class="size-6" />
                            @endif
                        </span>
                        <flux:icon.arrow-up-right class="size-5 text-zinc-300 transition group-hover:text-[#88AC2E]" />
                    </div>
                    <h2 class="mt-5 text-lg font-black text-zinc-950 dark:text-white">
                        {{ $esPreescolar ? 'Fichas descriptivas' : 'Calificaciones' }} · {{ $nivel->nombre }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                        {{ $esPreescolar ? 'Captura las fichas de tus grupos asignados.' : 'Captura, revisa y confirma tus calificaciones.' }}
                    </p>
                </a>
            @endforeach
        </section>

        <section class="rounded-[24px] border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-zinc-950 dark:text-white">Entregas recientes</h2>
                    <p class="mt-1 text-sm text-zinc-500">Comprobantes consolidados generados al confirmar calificaciones.</p>
                </div>
            </div>

            <div class="mt-5 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($entregas as $entrega)
                    <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-zinc-900 dark:text-white">{{ $entrega->folio }}</p>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ $entrega->nivel?->nombre }} · {{ $entrega->grado?->nombre }} · {{ $entrega->grupo?->asignacionGrupo?->nombre ?? 'Grupo' }} · {{ $entrega->confirmada_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <a href="{{ route('docente.entregas.pdf', $entrega) }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-zinc-200 px-4 py-2 text-xs font-bold text-zinc-700 transition hover:border-[#006492]/40 hover:text-[#006492] dark:border-zinc-700 dark:text-zinc-200">
                            <flux:icon.arrow-down-tray class="size-4" /> PDF
                        </a>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-200 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        Aún no tienes entregas confirmadas.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
