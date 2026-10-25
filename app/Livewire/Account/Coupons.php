<?php

namespace App\Livewire\Account;

use App\Models\RouletteSpin;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.account')]
class Coupons extends Component
{
    public function claim(int $spinId): void
    {
        $spin = RouletteSpin::query()
            ->where('user_id', auth()->id())
            ->with('coupon')
            ->findOrFail($spinId);

        if ($spin->coupon && ! $spin->claimed_at) {
            $spin->update(['claimed_at' => now()]);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Cupón reclamado correctamente.',
            ]);
        }
    }

    public function render()
    {
        $spins = RouletteSpin::query()
            ->where('user_id', auth()->id())
            ->with(['coupon.usages.order'])
            ->latest('id')
            ->get();

        return view('livewire.account.coupons', compact('spins'))
            ->title('Mis cupones ganados · Brevare Perú');
    }
}
