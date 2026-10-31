<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointRedemption;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointsService
{
    public const REGISTRATION_POINTS = 50;

    public const MIN_REDEMPTION = 1000;

    public const POINTS_PER_REWARD = 1000;

    public const REWARD_AMOUNT = 50.00;

    public const POINTS_PER_SOL = 1;

    public function awardRegistration(User $user): ?PointTransaction
    {
        return DB::transaction(function () use ($user) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (! $locked) {
                return null;
            }

            $existing = PointTransaction::query()
                ->where('user_id', $locked->id)
                ->where('type', 'registration')
                ->where('reference_type', 'user')
                ->where('reference_id', $locked->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $points = self::REGISTRATION_POINTS;
            $locked->increment('points_balance', $points);
            $locked->refresh();

            return PointTransaction::create([
                'user_id' => $locked->id,
                'points' => $points,
                'balance_after' => (int) $locked->points_balance,
                'type' => 'registration',
                'description' => 'Bono de bienvenida Brevare Puntos',
                'reference_type' => 'user',
                'reference_id' => $locked->id,
            ]);
        });
    }

    public function awardOrder(Order $order): ?PointTransaction
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->points_awarded_at) {
                return null;
            }

            $productAmount = max(
                0,
                (float) $lockedOrder->subtotal - (float) $lockedOrder->discount_total
            );
            $points = max(0, (int) floor($productAmount));

            if ($points === 0 || ! $lockedOrder->user_id) {
                return null;
            }

            $user = User::query()->whereKey($lockedOrder->user_id)->lockForUpdate()->first();

            if (! $user) {
                return null;
            }

            $existing = PointTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'purchase')
                ->where('reference_type', 'order')
                ->where('reference_id', $lockedOrder->id)
                ->first();

            if ($existing) {
                $lockedOrder->forceFill([
                    'points_earned' => $existing->points,
                    'points_awarded_at' => now(),
                ])->save();

                return $existing;
            }

            $user->increment('points_balance', $points);
            $user->refresh();

            $transaction = PointTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'balance_after' => (int) $user->points_balance,
                'type' => 'purchase',
                'description' => 'Compra '.$lockedOrder->order_number,
                'reference_type' => 'order',
                'reference_id' => $lockedOrder->id,
                'metadata' => ['total' => (float) $lockedOrder->total],
            ]);

            $lockedOrder->forceFill([
                'points_earned' => $points,
                'points_awarded_at' => now(),
            ])->save();

            return $transaction;
        });
    }

    public function redeem(User $user, int $points): PointRedemption
    {
        if ($points < self::MIN_REDEMPTION || $points % self::POINTS_PER_REWARD !== 0) {
            throw ValidationException::withMessages([
                'pointsToRedeem' => 'El canje mínimo es de 1,000 puntos y debe ser múltiplo de 1,000.',
            ]);
        }

        return DB::transaction(function () use ($user, $points) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->points_balance < $points) {
                throw ValidationException::withMessages([
                    'pointsToRedeem' => 'No tienes suficientes puntos disponibles.',
                ]);
            }

            $amount = $this->rewardAmount($points);
            $redemption = PointRedemption::create([
                'user_id' => $locked->id,
                'points' => $points,
                'amount' => $amount,
                'status' => PointRedemption::STATUS_PENDING,
            ]);

            $locked->decrement('points_balance', $points);
            $locked->refresh();

            PointTransaction::create([
                'user_id' => $locked->id,
                'points' => -$points,
                'balance_after' => (int) $locked->points_balance,
                'type' => 'redemption',
                'description' => 'Solicitud de canje #'.$redemption->id,
                'reference_type' => 'redemption',
                'reference_id' => $redemption->id,
                'metadata' => ['amount' => $amount],
            ]);

            return $redemption;
        });
    }

    public function approve(PointRedemption $redemption, User $admin, ?string $notes = null): PointRedemption
    {
        return DB::transaction(function () use ($redemption, $admin, $notes) {
            $locked = PointRedemption::query()->whereKey($redemption->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [
                PointRedemption::STATUS_APPROVED,
                PointRedemption::STATUS_PAID,
            ], true)) {
                return $locked;
            }

            if ($locked->status !== PointRedemption::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'redemption' => 'Este canje ya no se puede aprobar.',
                ]);
            }

            $locked->update([
                'status' => PointRedemption::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => $notes ?: $locked->admin_notes,
            ]);

            return $locked->fresh();
        });
    }

    public function markPaid(
        PointRedemption $redemption,
        User $admin,
        ?string $reference = null,
        ?string $notes = null,
    ): PointRedemption {
        return DB::transaction(function () use ($redemption, $admin, $reference, $notes) {
            $locked = PointRedemption::query()->whereKey($redemption->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PointRedemption::STATUS_PAID) {
                return $locked;
            }

            if (! in_array($locked->status, [
                PointRedemption::STATUS_PENDING,
                PointRedemption::STATUS_APPROVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'redemption' => 'Este canje no está disponible para depósito.',
                ]);
            }

            $locked->update([
                'status' => PointRedemption::STATUS_PAID,
                'payout_reference' => $reference,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'paid_at' => now(),
                'admin_notes' => $notes ?: $locked->admin_notes,
            ]);

            return $locked->fresh();
        });
    }

    public function reject(
        PointRedemption $redemption,
        User $admin,
        ?string $reason = null,
    ): PointRedemption {
        return DB::transaction(function () use ($redemption, $admin, $reason) {
            $locked = PointRedemption::query()->whereKey($redemption->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PointRedemption::STATUS_REJECTED) {
                return $locked;
            }

            if (! in_array($locked->status, [
                PointRedemption::STATUS_PENDING,
                PointRedemption::STATUS_APPROVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'redemption' => 'Este canje ya fue depositado y no se puede rechazar.',
                ]);
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();

            $existingRefund = PointTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'redemption_refund')
                ->where('reference_type', 'redemption')
                ->where('reference_id', $locked->id)
                ->exists();

            if (! $existingRefund) {
                $user->increment('points_balance', (int) $locked->points);
                $user->refresh();

                PointTransaction::create([
                    'user_id' => $user->id,
                    'points' => (int) $locked->points,
                    'balance_after' => (int) $user->points_balance,
                    'type' => 'redemption_refund',
                    'description' => 'Reembolso de canje #'.$locked->id,
                    'reference_type' => 'redemption',
                    'reference_id' => $locked->id,
                ]);
            }

            $locked->update([
                'status' => PointRedemption::STATUS_REJECTED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_notes' => $reason ?: $locked->admin_notes,
            ]);

            return $locked->fresh();
        });
    }

    public function rewardAmount(int $points): float
    {
        return round(
            ($points / self::POINTS_PER_REWARD) * self::REWARD_AMOUNT,
            2
        );
    }
}
