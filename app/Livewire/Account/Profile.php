<?php

namespace App\Livewire\Account;

use App\Services\CloudinaryImageService;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.account')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $avatar = '';

    /**
     * Avatar seleccionado para subir a Cloudinary.
     */
    public $avatar_file = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->avatar = $user->avatar ?? '';
    }

    /**
     * Al elegir un archivo de avatar lo valida y muestra la vista previa.
     */
    public function updatedAvatarFile(): void
    {
        $this->validate([
            'avatar_file' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    public function updateProfile(
        EmailVerificationService $verification,
        CloudinaryImageService $cloudinary
    ): void {
        $user = Auth::user();

        if ($this->avatar_file) {
            $this->validate([
                'avatar_file' => ['required', 'image', 'max:5120'],
            ]);
        }

        // Subir el avatar a Cloudinary si se eligió uno nuevo.
        if ($this->avatar_file) {
            $uploaded = $cloudinary->upload($this->avatar_file, CloudinaryImageService::FOLDER_AVATARS);

            // Eliminar el avatar anterior en Cloudinary si existía.
            if ($this->avatar) {
                $cloudinary->delete($cloudinary->publicIdFromUrl($this->avatar));
            }

            $this->avatar = $uploaded['secure_url'];
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'url', 'max:2048'],
        ]);

        $emailChanged = strtolower($this->email) !== strtolower($user->email);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] !== '' ? $validated['phone'] : null,
            'avatar' => $validated['avatar'] !== '' ? $validated['avatar'] : null,
        ])->save();

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $verification->sendCode($user);

            $this->redirectRoute('verification.notice');

            return;
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tu perfil se actualizó correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.account.profile');
    }
}
