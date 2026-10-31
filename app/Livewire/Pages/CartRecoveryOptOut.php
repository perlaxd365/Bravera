<?php

namespace App\Livewire\Pages;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class CartRecoveryOptOut extends Component
{
    public function mount(User $user): void
    {
        $user->forceFill(['marketing_opt_out_at' => now()])->save();
    }

    public function render()
    {
        return view('livewire.pages.cart-recovery-opt-out')
            ->title('Preferencias de correo | Brevare')
            ->layoutData([
                'seoDescription' => 'Preferencias de recordatorios de carrito de Brevare.',
                'canonicalUrl' => request()->url(),
                'noIndex' => true,
            ]);
    }
}
