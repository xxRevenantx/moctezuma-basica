<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">


            {{-- IMAGEN DE USUARIO --}}
            <div x-data="{
                preview: @js(auth()->user()->profile_photo_url ?? null),
                handleChange(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = e => this.preview = e.target.result;
                    reader.readAsDataURL(file);
                }
            }" class="w-full">
                <flux:field :label="__('Profile photo')" class="space-y-3">
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        {{-- Avatar preview --}}
                        <div class="relative">
                            <div
                                class="h-20 w-20 rounded-full border-2 border-indigo-500/70 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 overflow-hidden shadow-md">
                                <template x-if="preview">
                                    <img x-bind:src="preview" alt="Avatar preview"
                                        class="h-full w-full object-cover">
                                </template>
                                <template x-if="!preview">
                                    <div
                                        class="h-full w-full flex items-center justify-center text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('Sin foto') }}
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Dropzone bonita --}}
                        <label for="photo" class="flex-1 cursor-pointer">
                            <div
                                class="relative w-full rounded-2xl border-2 border-dashed border-indigo-400/70 bg-gradient-to-r from-indigo-50 via-violet-50 to-sky-50
                                        dark:from-slate-900 dark:via-slate-900 dark:to-slate-900
                                        px-4 py-4 sm:px-5 sm:py-5
                                        flex flex-col sm:flex-row items-center sm:items-start gap-3
                                        shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div
                                    class="inline-flex h-9 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 to-violet-600
                                           px-4 text-xs font-medium text-white shadow-md">
                                    {{ __('Subir imagen') }}
                                </div>

                                <div class="flex-1 text-center sm:text-left space-y-1">
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">
                                        {{ __('Arrastra una imagen aquí o haz clic para seleccionar') }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('JPG, PNG o WEBP. Máx. 2MB.') }}
                                    </p>
                                </div>
                            </div>

                            <input id="photo" type="file" class="hidden" accept="image/*" wire:model="photo"
                                x-on:change="handleChange">
                        </label>
                    </div>

                    @error('photo')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </flux:field>
            </div>



            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus
                autocomplete="name" />
            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer"
                                wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

    </x-settings.layout>
</section>
