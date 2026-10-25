<?php

namespace App\Livewire\Store;

use App\Models\RouletteSpin;
use App\Services\RouletteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use RuntimeException;

class RouletteOverlay extends Component
{
    private const GUEST_PRIZE_KEY = 'brevare_roulette_guest_spin';

    public ?int $lastSpinId = null;

    public ?array $guestPrize = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->guestPrize = Session::get(self::GUEST_PRIZE_KEY);

        if (Auth::check()) {
            $this->lastSpinId = Auth::user()->rouletteSpins()->latest('id')->value('id');
        }
    }

    public function spin(RouletteService $roulette): void
    {
        $this->error = null;

        if (! Auth::check()) {
            if ($this->guestPrize) {
                $this->error = 'Ya giraste en esta sesión. Inicia sesión para reclamar tu resultado.';
                $this->dispatch('roulette-error');

                return;
            }

            $result = $roulette->spinGuest();
            $this->guestPrize = [
                'index' => $result['index'],
                ...$result['segment'],
            ];
            Session::put(self::GUEST_PRIZE_KEY, $this->guestPrize);
            $this->dispatch('roulette-result', [
                'index' => $result['index'],
                'result' => $result['segment']['result'],
            ]);

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

    public function claimPending(RouletteService $roulette): void
    {
        if (! Auth::check()) {
            $this->dispatch('roulette-require-login', [
                'url' => route('login', ['redirect' => request()->fullUrl()]),
            ]);

            return;
        }

        $pending = Session::get(self::GUEST_PRIZE_KEY);

        if (! is_array($pending)) {
            $this->error = 'No encontramos un premio pendiente para reclamar.';

            return;
        }

        try {
            $spin = $roulette->claimGuestSpin(Auth::user(), $pending);
        } catch (RuntimeException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Session::forget(self::GUEST_PRIZE_KEY);
        $this->guestPrize = null;
        $this->lastSpinId = $spin->id;

        if ($spin->coupon && ! $spin->claimed_at) {
            $spin->update(['claimed_at' => now()]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $spin->coupon
                ? 'Premio reclamado. Ya está vinculado a tu cuenta.'
                : 'Resultado reclamado.',
        ]);
    }

    public function claim(int $spinId, RouletteService $roulette): void
    {
        if (! Auth::check()) {
            return;
        }

        $spin = RouletteSpin::query()
            ->where('user_id', Auth::id())
            ->with('coupon')
            ->findOrFail($spinId);

        if ($spin->coupon && ! $spin->claimed_at) {
            $spin->update(['claimed_at' => now()]);
        }

        $this->lastSpinId = $spin->id;
        $this->error = $roulette->eligibility(Auth::user())['message'];
    }

    public function render(RouletteService $roulette)
    {
        $user = Auth::user();
        $eligibility = $user
            ? $roulette->eligibility($user)
            : ['allowed' => ! $this->guestPrize, 'message' => 'Gira gratis y luego inicia sesión para reclamar tu premio.', 'coupon' => null];
        $lastSpin = $user && $this->lastSpinId
            ? $user->rouletteSpins()->with('coupon')->find($this->lastSpinId)
            : null;

        return view('livewire.store.roulette-overlay', [
            'segments' => $roulette->segments(),
            'eligibility' => $eligibility,
            'lastSpin' => $lastSpin,
        ]);
    }
}
