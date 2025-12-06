<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public $photo;

    /**
     * Mount the component.
     */
    #[On('refreshProfile')]
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->photo = Auth::user()->photo;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();



        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,jpg,png'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        // Si se sube una nueva foto...
        if ($this->photo) {
            // Elimina la imagen anterior si no es la default
            if ($user->photo && $user->photo !== 'default.jpg') {
                Storage::delete('profile-photos/' . $user->photo);
            }

            // Guarda la nueva imagen
            $path = $this->photo->store('profile-photos');
            $validated['photo'] = str_replace('profile-photos/', '', $path);
        } else {
            unset($validated['photo']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('refreshHeader');
        $this->dispatch('refreshProfile');

        $this->dispatch('profile-updated', name: $user->name);
    }


    public function removePhoto()
    {
        // Borra el archivo físico si existe
        if (auth()->user()->photo) {
            Storage::disk('public')->delete('profile-photos/' . auth()->user()->photo);
            auth()->user()->update(['photo' => null]);
        }

        $this->dispatch('refreshHeader');
        $this->dispatch('refreshProfile');

        // Resetea el campo de carga
        $this->reset('photo');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
