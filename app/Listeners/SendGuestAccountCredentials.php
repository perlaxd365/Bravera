<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Mail\GuestAccountCredentialsMail;
use App\Models\User;
use App\Modules\Ordering\Events\OrderPaid;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendGuestAccountCredentials implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 1;

    /**
     * Activa la cuenta temporal de invitado solo después de un pago confirmado.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order->fresh();

        if ($order?->payment_status !== PaymentStatus::PAID) {
            return;
        }

        $password = Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);

        $user = DB::transaction(function () use ($order, $password): ?User {
            $user = User::query()->lockForUpdate()->find($order->user_id);

            if ($user === null || ! $user->is_guest) {
                return null;
            }

            $user->forceFill([
                'password' => $password,
                'is_guest' => false,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();

            try {
                $user->assignCustomerRole();
            } catch (Throwable) {
                Log::warning('No se pudo asignar el rol Cliente a la cuenta creada en checkout.', [
                    'user_id' => $user->id,
                ]);
            }

            return $user->fresh();
        });

        if ($user === null) {
            return;
        }

        ReliableMailer::send(
            [$user->email],
            new GuestAccountCredentialsMail($user, $password, $order),
            description: 'credenciales de la cuenta creada en checkout',
            context: ['user_id' => $user->id, 'order_id' => $order->id],
        );
    }
}
