<?php

namespace App\Livewire\Store;

use App\Models\RouletteSpin;
use App\Services\RouletteService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

class Roulette extends Component
{
    public ?int $lastSpinId = null;

    public ?string $error = null;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->lastSpinId = Auth::user()->rouletteSpins()->latest('id')->value('id');
        }
    }

    public function spin(RouletteService $roulette): void
    {
        $this->error = null;

        if (! Auth::check()) {
            $this->error = 'Inicia sesión para girar y guardar tu cupón.';
            $this->dispatch('roulette-error');

            return;
        }

        try {
            $result = $roulette->spin(Auth::user());
        } catch (RuntimeException $exception) {
            $this->error = $exception->getMessage();
            $this->dispatch('roulette-error');

            return;
        }

        $this->lastSpinId = $result['spin']->id;
        $this->dispatch('roulette-result', [
            'index' => $result['index'],
            'result' => $result['segment']['result'],
        ]);
    }

    public function claim(int $spinId, RouletteService $roulette): void
    {
        abort_unless(Auth::check(), 403);

        $spin = RouletteSpin::query()
            ->where('user_id', Auth::id())
            ->with('coupon')
            ->findOrFail($spinId);

        if (! $spin->coupon) {
            return;
        }

        if (! $spin->claimed_at) {
            $spin->update(['claimed_at' => now()]);
        }

        $this->lastSpinId = $spin->id;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cupón reclamado. Ya está disponible en tu cuenta.',
        ]);

        // Mantiene la misma puerta de elegibilidad mientras el cupón no se usa.
        $this->error = $roulette->eligibility(Auth::user())['message'];
    }

    #[Layout('store.layouts.app')]
    public function render(RouletteService $roulette)
    {
        $user = Auth::user();
        $eligibility = $user
            ? $roulette->eligibility($user)
            : ['allowed' => false, 'message' => 'Inicia sesión para participar.', 'coupon' => null];
        $lastSpin = $user && $this->lastSpinId
            ? $user->rouletteSpins()->with('coupon')->find($this->lastSpinId)
            : null;

        return view('livewire.store.roulette', [
            'segments' => $roulette->segments(),
            'eligibility' => $eligibility,
            'lastSpin' => $lastSpin,
        ])->title('Ruleta Brevare Perú');
    }
}
