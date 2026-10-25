<?php

namespace App\Services;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\RouletteSpin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RouletteService
{
    /**
     * Diez segmentos con la misma probabilidad. Las repeticiones determinan
     * la frecuencia de cada premio sin ocultar las condiciones.
     *
     * @return array<int, array{result: string, label: string, percent: int, min: float}>
     */
    public function segments(): array
    {
        return [
            ['result' => '15', 'label' => '15%', 'percent' => 15, 'min' => 100.0],
            ['result' => '10', 'label' => '10%', 'percent' => 10, 'min' => 40.0],
            ['result' => '5', 'label' => '5%', 'percent' => 5, 'min' => 0.0],
            ['result' => 'none', 'label' => 'Sigue intentando', 'percent' => 0, 'min' => 0.0],
            ['result' => '5', 'label' => '5%', 'percent' => 5, 'min' => 0.0],
            ['result' => '15', 'label' => '15%', 'percent' => 15, 'min' => 100.0],
            ['result' => 'none', 'label' => 'Sigue intentando', 'percent' => 0, 'min' => 0.0],
            ['result' => '10', 'label' => '10%', 'percent' => 10, 'min' => 40.0],
            ['result' => '5', 'label' => '5%', 'percent' => 5, 'min' => 0.0],
            ['result' => 'none', 'label' => 'Sigue intentando', 'percent' => 0, 'min' => 0.0],
        ];
    }

    /**
     * @return array{allowed: bool, message: string, coupon: ?Coupon}
     */
    public function eligibility(User $user): array
    {
        $lastSpin = $user->rouletteSpins()
            ->with('coupon')
            ->latest('id')
            ->first();

        if (! $lastSpin || ! $lastSpin->coupon) {
            return [
                'allowed' => true,
                'message' => 'Ya puedes girar la ruleta Brevare.',
                'coupon' => null,
            ];
        }

        $coupon = $lastSpin->coupon;
        $used = $coupon->usages()
            ->whereHas('order', fn ($query) => $query->where('payment_status', 'paid'))
            ->exists();

        return [
            'allowed' => $used,
            'message' => $used
                ? 'Tu cupón anterior ya fue usado. Tienes un nuevo giro disponible.'
                : 'Para volver a girar debes usar el cupón que ganaste en una compra confirmada.',
            'coupon' => $coupon,
        ];
    }

    public function spin(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $eligibility = $this->eligibility($user);

            if (! $eligibility['allowed']) {
                throw new RuntimeException($eligibility['message']);
            }

            [$index, $segment] = $this->drawSegment();
            $spin = $this->persistSpin($user, $segment);

            return [
                'spin' => $spin->load('coupon'),
                'index' => $index,
                'segment' => $segment,
            ];
        });
    }

    public function spinGuest(): array
    {
        [$index, $segment] = $this->drawSegment();

        return [
            'spin' => null,
            'index' => $index,
            'segment' => $segment,
        ];
    }

    public function claimGuestSpin(User $user, array $guestSpin): RouletteSpin
    {
        return DB::transaction(function () use ($user, $guestSpin): RouletteSpin {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! $this->eligibility($user)['allowed']) {
                throw new RuntimeException(
                    'Para reclamar este premio primero debes usar el cupón anterior de tu cuenta.'
                );
            }

            return $this->persistSpin($user, [
                'result' => (string) ($guestSpin['result'] ?? 'none'),
                'label' => (string) ($guestSpin['label'] ?? 'Sigue intentando'),
                'percent' => (int) ($guestSpin['percent'] ?? 0),
                'min' => (float) ($guestSpin['min'] ?? 0),
            ])->load('coupon');
        });
    }

    private function drawSegment(): array
    {
        $segments = $this->segments();
        $index = random_int(0, count($segments) - 1);

        return [$index, $segments[$index]];
    }

    private function persistSpin(User $user, array $segment): RouletteSpin
    {
        $coupon = null;

        if ($segment['percent'] > 0) {
            $coupon = Coupon::create([
                'code' => $this->uniqueCode(),
                'name' => 'Ruleta Brevare Perú · '.$segment['label'],
                'type' => CouponType::PERCENTAGE,
                'value' => $segment['percent'],
                'min_subtotal' => $segment['min'],
                'max_discount' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'usage_limit' => 1,
                'per_user_limit' => 1,
                'applies_to' => CouponAppliesTo::ALL,
                'applies_to_id' => null,
                'is_active' => true,
                'user_id' => $user->id,
                'created_by' => null,
            ]);
        }

        return RouletteSpin::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon?->id,
            'result' => $segment['result'],
            'discount_percent' => $segment['percent'],
            'min_subtotal' => $segment['min'],
        ]);
    }

    private function uniqueCode(): string
    {
        do {
            $code = 'BV-'.Str::upper(Str::random(8));
        } while (Coupon::query()->where('code', $code)->exists());

        return $code;
    }
}
