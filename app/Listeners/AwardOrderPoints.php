<?php

namespace App\Listeners;

use App\Mail\PointsEarnedMail;
use App\Modules\Ordering\Events\OrderPaid;
use App\Services\PointsService;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AwardOrderPoints implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPaid $event): void
    {
        $transaction = app(PointsService::class)->awardOrder($event->order);

        if (! $transaction || ! $transaction->wasRecentlyCreated) {
            return;
        }

        $user = $event->order->user?->fresh();

        if (! $user?->email) {
            return;
        }

        ReliableMailer::send(
            [$user->email],
            new PointsEarnedMail(
                $user,
                $transaction->points,
                $transaction->balance_after,
                $event->order->order_number,
            ),
            description: 'correo de puntos ganados',
            context: ['user_id' => $user->id, 'order_id' => $event->order->id],
        );
    }
}
