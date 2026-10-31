<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\AbandonedCartReminder;
use App\Models\Cart;
use App\Services\ReliableMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendAbandonedCartEmails extends Command
{
    protected $signature = 'carts:send-recovery
        {--days= : Días mínimos de inactividad}
        {--limit=100 : Máximo de carritos a procesar}
        {--dry-run : Muestra los carritos sin enviar correos}';

    protected $description = 'Envía recordatorios de carritos abandonados cada N días.';

    public function handle(): int
    {
        if (! config('cart_recovery.enabled', true)) {
            $this->warn('La recuperación de carritos está desactivada.');

            return self::SUCCESS;
        }

        $days = max(1, (int) ($this->option('days') ?: config('cart_recovery.days', 3)));
        $limit = max(1, (int) $this->option('limit'));
        $maxEmails = max(1, (int) config('cart_recovery.max_emails', 3));

        $carts = Cart::query()
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->whereHas('user', fn ($query) => $query
                ->whereNotNull('email')
                ->whereNull('marketing_opt_out_at'))
            ->whereHas('activeItems')
            ->whereDoesntHave('orders', fn ($query) => $query
                ->where('status', OrderStatus::PENDING)
                ->where('payment_status', PaymentStatus::PENDING))
            ->where(function ($query) use ($days) {
                $query->where(function ($query) use ($days) {
                    $query->whereNull('last_activity_at')
                        ->where('updated_at', '<=', now()->subDays($days));
                })->orWhere('last_activity_at', '<=', now()->subDays($days));
            })
            ->where(function ($query) use ($days) {
                $query->whereNull('recovery_email_sent_at')
                    ->orWhere('recovery_email_sent_at', '<=', now()->subDays($days));
            })
            ->where('recovery_email_count', '<', $maxEmails)
            ->with([
                'user',
                'items.variant.product',
                'items.variant.images',
                'items.variant.attributeValues.attribute',
                'items.variant.attributeValues.value',
            ])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($carts as $cart) {
            $email = $cart->user?->email;

            if (blank($email) || $cart->items->isEmpty()) {
                $skipped++;

                continue;
            }

            if (blank($cart->recovery_token)) {
                do {
                    $token = Str::random(48);
                } while (Cart::query()->where('recovery_token', $token)->exists());

                $cart->forceFill(['recovery_token' => $token])->save();
            }

            if (! $cart->last_activity_at) {
                $cart->forceFill(['last_activity_at' => $cart->updated_at])->save();
            }

            if ($this->option('dry-run')) {
                $this->line("Carrito #{$cart->id} → {$email}");
                $sent++;

                continue;
            }

            $ok = ReliableMailer::send(
                [$email],
                new AbandonedCartReminder($cart),
                description: 'recordatorio de carrito abandonado',
                context: ['cart_id' => $cart->id],
            );

            if (! $ok) {
                $skipped++;

                continue;
            }

            $cart->forceFill([
                'recovery_email_sent_at' => now(),
                'recovery_email_count' => ((int) $cart->recovery_email_count) + 1,
            ])->save();
            $sent++;
        }

        $this->info($this->option('dry-run')
            ? "Carritos candidatos: {$sent}; omitidos: {$skipped}."
            : "Recordatorios enviados: {$sent}; omitidos: {$skipped}.");

        return self::SUCCESS;
    }
}
