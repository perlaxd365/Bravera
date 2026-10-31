<?php

namespace App\Livewire\Account;

use App\Mail\PointsRedeemedMail;
use App\Services\PointsService;
use App\Services\ReliableMailer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.account')]
class Points extends Component
{
    public int $pointsToRedeem = 1000;

    public function mount(): void
    {
        $this->pointsToRedeem = PointsService::MIN_REDEMPTION;
    }

    public function redeem(): void
    {
        $this->validate([
            'pointsToRedeem' => ['required', 'integer', 'min:'.PointsService::MIN_REDEMPTION],
        ], [
            'pointsToRedeem.required' => 'Selecciona cuántos puntos quieres canjear.',
            'pointsToRedeem.integer' => 'La cantidad de puntos no es válida.',
            'pointsToRedeem.min' => 'El canje mínimo es de 1,000 puntos.',
        ]);

        $user = auth()->user();
        $redemption = app(PointsService::class)->redeem($user, $this->pointsToRedeem);
        $user->refresh();

        ReliableMailer::send(
            [$user->email],
            new PointsRedeemedMail($user, $redemption),
            description: 'correo de solicitud de canje de puntos',
            context: ['user_id' => $user->id, 'redemption_id' => $redemption->id],
        );

        $this->pointsToRedeem = PointsService::MIN_REDEMPTION;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Solicitud enviada. Validaremos el canje y te avisaremos por correo.',
        ]);
    }

    public function render()
    {
        $user = auth()->user()->fresh();
        $balance = (int) $user->points_balance;
        $min = PointsService::MIN_REDEMPTION;
        $canRedeem = $balance >= $min;
        $maxRedeemable = $canRedeem ? intdiv($balance, $min) * $min : 0;
        $redeemOptions = $canRedeem
            ? range($min, $maxRedeemable, $min)
            : [];

        $progress = $balance < $min
            ? (int) round(($balance / $min) * 100)
            : ($balance % $min === 0
                ? 100
                : (int) round((($balance % $min) / $min) * 100));

        $pointsToNext = $balance < $min
            ? $min - $balance
            : ($min - ($balance % $min));

        $transactions = $user->pointTransactions()
            ->latest()
            ->limit(20)
            ->get();

        $redemptions = $user->pointRedemptions()
            ->latest()
            ->limit(10)
            ->get();

        $rewardAmount = app(PointsService::class)->rewardAmount($balance);

        return view('livewire.account.points', compact(
            'user',
            'balance',
            'canRedeem',
            'redeemOptions',
            'progress',
            'pointsToNext',
            'transactions',
            'redemptions',
            'rewardAmount',
        ));
    }
}
