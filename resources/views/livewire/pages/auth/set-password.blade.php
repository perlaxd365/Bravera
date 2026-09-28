<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Si el usuario ya tiene contraseña, esta pantalla no aplica.
     */
    public function mount(): void
    {
        if (! Auth::user()->needsPasswordSetup()) {
            $this->redirectRoute('home', navigate: true);
        }
    }

    /**
     * Crea la contraseña de Brevare confirmándola dos veces.
     */
    public function savePassword(): void
    {
        $user = Auth::user();

        if (! $user->needsPasswordSetup()) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        $this->redirectRoute('home', navigate: true);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <x-brevare.auth-card
        title="Crea tu contraseña"
        subtitle="Tu cuenta de Google está vinculada. Solo falta crear una contraseña para entrar también con tu correo."
    >
        <div class="auth-rise flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3" style="animation-delay: 60ms">
            <img
                src="{{ Auth::user()->avatar }}"
                alt=""
                onerror="this.parentElement.querySelector('svg').classList.remove('hidden'); this.classList.add('hidden');"
                class="size-10 shrink-0 rounded-full object-cover"
            >
            <svg class="size-10 shrink-0 rounded-full bg-gray-200 p-2 text-gray-500 hidden" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.46-8 5.5V22h16v-2.5c0-3.04-3.58-5.5-8-5.5z" />
            </svg>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500">Ingresaste con</p>
                <p class="truncate text-sm font-semibold text-gray-900">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <form wire:submit="savePassword" class="mt-6 space-y-5" novalidate>
            <x-brevare.password-input
                wire:model="password"
                id="password"
                label="Nueva contraseña"
                strength
                autocomplete="new-password"
                required
            />

            <x-brevare.password-input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="Confirma tu contraseña"
                autocomplete="new-password"
                required
            />

            <x-brevare.button class="w-full">
                Guardar y continuar
            </x-brevare.button>
        </form>

        <x-slot:footer>
            <button
                type="button"
                wire:click="logout"
                class="mx-auto flex items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-900"
            >
                <flux:icon name="arrow-right-start-on-rectangle" class="size-4" />
                Cerrar sesión
            </button>
        </x-slot:footer>
    </x-brevare.auth-card>
</div>