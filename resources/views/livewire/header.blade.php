<div class="w-full mx-auto ">

    <!-- BARRA SUPERIOR -->

    {{-- {{ auth()->user() }} --}}

    <div
        class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 shadow-lg overflow-hidden mb-4">
        <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
        <div class="p-3 sm:p-3">
            <div class="md:flex md:justify-between gap-4">
                <!-- Fecha -->
                <div
                    class="flex items-center gap-2 w-full sm:w-auto justify-center lg:justify-start text-neutral-700 dark:text-neutral-100">
                    <div
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        <flux:icon.calendar />
                    </div>
                    <span class="font-medium">{{ now()->translatedFormat('d \d\e F \d\e Y') }}</span>
                </div>

                <!-- Widgets -->
                <div class="w-full sm:w-auto flex flex-col lg:flex-row items-center gap-3 mt-2 sm:mt-0">

                    {{-- Disparador del buscador global. El componente vive fuera de Header para evitar
                         que los eventos wire del modal sean enviados al componente padre. --}}
                    @if (auth()->user()?->is_admin)
                        <button type="button"
                            x-data="{
                                abriendo: false,
                                abrirBuscador() {
                                    if (this.abriendo) return;

                                    this.abriendo = true;
                                    window.dispatchEvent(new CustomEvent('buscador-global-iniciando'));
                                    window.Livewire.dispatch('abrir-buscador-global');

                                    // Respaldo visual por si la petición falla antes de responder.
                                    setTimeout(() => this.abriendo = false, 5000);
                                }
                            }"
                            x-on:click="abrirBuscador()"
                            x-on:buscador-global-iniciando.window="abriendo = true"
                            x-on:buscador-global-abierto.window="abriendo = false"
                            x-bind:disabled="abriendo"
                            x-bind:aria-busy="abriendo.toString()"
                            class="group flex h-10 w-full min-w-0 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 text-left text-sm text-neutral-500 shadow-sm transition hover:border-[#006492]/40 hover:bg-sky-50/60 hover:text-[#006492] focus:outline-none focus:ring-4 focus:ring-[#006492]/10 disabled:cursor-wait disabled:opacity-80 dark:border-neutral-600 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-500/10 sm:w-[270px] lg:w-[330px]"
                            aria-label="Abrir búsqueda global">
                            <flux:icon.magnifying-glass class="size-4 shrink-0 transition group-hover:scale-110" />

                            <span class="min-w-0 flex-1 truncate">
                                Buscar alumno, folio, calificación...
                            </span>

                            <span x-cloak x-show="abriendo" class="inline-flex shrink-0 items-center" aria-hidden="true">
                                <svg class="size-4 animate-spin text-[#006492] dark:text-sky-300" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".2" stroke-width="3"></circle>
                                    <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </span>

                            <kbd x-show="!abriendo"
                                class="hidden shrink-0 rounded-lg border border-neutral-200 bg-neutral-50 px-2 py-1 text-[10px] font-black text-neutral-500 shadow-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 sm:inline-flex">
                                Ctrl K
                            </kbd>
                        </button>
                    @endif

                    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                        <flux:radio value="light" icon="sun"></flux:radio>
                        <flux:radio value="dark" icon="moon"></flux:radio>
                    </flux:radio.group>


                    <!-- Chips -->
                    <div class="inline-flex items-center gap-2">
                        <div
                            class="rounded-xl px-3 py-2 border border-neutral-200 dark:border-neutral-600 bg-neutral-50 dark:bg-neutral-700/40 text-sm text-neutral-800 dark:text-neutral-100">
                            Ciclo escolar
                            <flux:badge color="indigo" class="ml-2">
                                {{ $cicloEscolar ? $cicloEscolar->inicio_anio . '-' . $cicloEscolar->fin_anio : '0' }}
                            </flux:badge>
                        </div>

                    </div>


                    @auth
                        @php($user = auth()->user())

                        {{-- Si hay usuario logueado --}}
                        @if ($user->photo)
                            <div class="relative w-10 h-10 hidden lg:block">
                                @if ($user->photo && file_exists(storage_path('app/public/profile-photos/' . $user->photo)))
                                    <div
                                        class="w-full h-full rounded-full overflow-hidden border-4 border-white shadow ring-1 ring-neutral-200 dark:ring-neutral-700">
                                        <img src="{{ asset('storage/profile-photos/' . $user->photo) }}" alt="Avatar"
                                            class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <flux:avatar circle badge badge:circle badge:color="green" :initials="$user->initials()"
                                        :name="$user->username" />
                                @endif

                                <span
                                    class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white dark:border-neutral-800 rounded-full shadow-md"></span>
                            </div>

                            <div class="w-full text-center lg:hidden">
                                <span class="block font-semibold text-neutral-800 dark:text-neutral-100">
                                    {{ $user->username }}
                                </span>
                            </div>
                        @else
                            {{-- Usuario logueado pero sin foto --}}
                            <flux:avatar circle badge badge:circle badge:color="green" :initials="$user->initials()"
                                :name="$user->username" />

                            <div class="w-full text-center lg:hidden">
                                <span class="block font-semibold text-neutral-800 dark:text-neutral-100">
                                    {{ $user->username }}
                                </span>
                            </div>
                        @endif
                    @else
                        {{-- Sin usuario autenticado (invitado) --}}
                        <flux:avatar badge badge:color="green" />

                        <div class="w-full text-center lg:hidden">
                            <span class="block font-semibold text-neutral-800 dark:text-neutral-100">
                                Invitado
                            </span>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

</div>
