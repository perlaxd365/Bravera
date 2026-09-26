<?php

namespace App\Modules\Discounts\Services;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * Valida un cupón y calcula el descuento.
     *
     * @param  Collection<int, CartItem>  $items
     * @return array{coupon: Coupon, discount: float, qualifying_subtotal: float, description: string}
     */
    public function validate(string $code, User $user, Collection $items, float $subtotal): array
    {
        $coupon = Coupon::active()->byCode($code)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón ingresado no existe.',
            ]);
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isAfter($now)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón aún no está vigente.',
            ]);
        }

        if ($coupon->ends_at && $coupon->ends_at->isBefore($now)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón ha expirado.',
            ]);
        }

        if ($coupon->hasReachedGlobalLimit()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón ya no está disponible.',
            ]);
        }

        if ($coupon->hasReachedUserLimit($user->id)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Ya usaste este cupón.',
            ]);
        }

        if ($subtotal < (float) $coupon->min_subtotal) {
            throw ValidationException::withMessages([
                'coupon_code' => 'El cupón requiere un subtotal mínimo de S/ '
                    .number_format((float) $coupon->min_subtotal, 2).'.',
            ]);
        }

        [$qualifyingSubtotal, $description] = $this->computeQualifyingSubtotal($coupon, $items, $subtotal);

        if ($qualifyingSubtotal <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Este cupón no aplica a los productos de tu carrito.',
            ]);
        }

        return [
            'coupon' => $coupon,
            'discount' => $this->computeDiscount($coupon, $qualifyingSubtotal),
            'qualifying_subtotal' => $qualifyingSubtotal,
            'description' => $description,
        ];
    }

    private function computeQualifyingSubtotal(Coupon $coupon, Collection $items, float $subtotal): array
    {
        if ($coupon->applies_to === CouponAppliesTo::ALL) {
            return [$subtotal, 'Aplica a todo el carrito.'];
        }

        $qualifying = 0.0;

        foreach ($items as $item) {
            // CartItem no tiene product() por defecto; se usa variant->product.
            $product = $item->variant?->product;

            $qualifies = match ($coupon->applies_to) {
                CouponAppliesTo::SUPPLIER => $item->supplier_variant_id
                    && $item->supplierVariant?->supplier_id === (int) $coupon->applies_to_id,
                CouponAppliesTo::PRODUCT => $product?->id === (int) $coupon->applies_to_id,
                CouponAppliesTo::CATEGORY => $product?->category_id === (int) $coupon->applies_to_id,
                default => true,
            };

            if ($qualifies) {
                $qualifying += (float) $item->unit_price * $item->quantity;
            }
        }

        return [$qualifying, 'Aplica solo a los productos elegibles del carrito.'];
    }

    private function computeDiscount(Coupon $coupon, float $qualifyingSubtotal): float
    {
        if ($coupon->type === CouponType::FIXED) {
            return round(min((float) $coupon->value, $qualifyingSubtotal), 2);
        }

        $discount = $qualifyingSubtotal * ((float) $coupon->value / 100);

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(min($discount, $qualifyingSubtotal), 2);
    }
}
