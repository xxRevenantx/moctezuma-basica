<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        // El archivo existente se presenta desde el modelo; este campo solo
        // conserva una carga temporal nueva. Evita validar un nombre de archivo
        // persistido como si fuera una imagen subida por Livewire.
        $this->photo = null;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isProfessor()) {
            // El nombre y el identificador institucional de una cuenta docente son
            // administrados desde el expediente de personal. No se confía en los
            // valores públicos enviados por Livewire.
            if ($this->name !== $user->name || mb_strtolower($this->email) !== mb_strtolower($user->email)) {
                $this->name = (string) $user->name;
                $this->email = (string) $user->email;

                throw ValidationException::withMessages([
                    'email' => 'El nombre y el acceso institucional del profesor solo pueden modificarse desde administración.',
                ]);
            }

            $validated = $this->validate([
                'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,jpg,png'],
            ]);
        } else {
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
        }

        if ($this->photo) {
            if ($user->photo && $user->photo !== 'default.jpg') {
                Storage::disk('public')->delete('profile-photos/'.$user->photo);
            }

            $path = $this->photo->store('profile-photos', 'public');
            $validated['photo'] = str_replace('profile-photos/', '', $path);
        } else {
            unset($validated['photo']);
        }

        if ($user->isProfessor()) {
            unset($validated['name'], $validated['email']);
        }

        $user->fill($validated);

        if (! $user->isProfessor() && $user->isDirty('email')) {
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
            if (auth()->user()->photo !== 'default.jpg') {
                Storage::disk('public')->delete('profile-photos/' . auth()->user()->photo);
            }
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
