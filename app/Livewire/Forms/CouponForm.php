<?php

namespace App\Livewire\Forms;

use App\Models\Coupon;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CouponForm extends Form
{
    public ?int $id = null;

    #[Validate('required|string|max:50')]
    public string $code = '';

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|in:percentage,fixed')]
    public string $type = 'percentage';

    #[Validate('required|numeric|min:0.01')]
    public string $value = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $min_subtotal = null;

    #[Validate('nullable|numeric|min:0')]
    public ?string $max_discount = null;

    #[Validate('nullable|date')]
    public ?string $starts_at = null;

    #[Validate('nullable|date')]
    public ?string $ends_at = null;

    #[Validate('nullable|integer|min:1')]
    public ?string $usage_limit = null;

    #[Validate('nullable|integer|min:1')]
    public ?string $per_user_limit = null;

    #[Validate('required|in:all,supplier,product,category')]
    public string $applies_to = 'all';

    #[Validate('nullable|integer')]
    public ?string $applies_to_id = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    public function resetForm(): void
    {
        $this->reset();
        $this->type = 'percentage';
        $this->applies_to = 'all';
        $this->is_active = true;
    }

    public function fromModel(Coupon $coupon): void
    {
        $this->id = $coupon->id;
        $this->code = $coupon->code;
        $this->name = $coupon->name;
        $this->type = $coupon->type->value;
        $this->value = (string) $coupon->value;
        $this->min_subtotal = $coupon->min_subtotal !== null ? (string) $coupon->min_subtotal : null;
        $this->max_discount = $coupon->max_discount !== null ? (string) $coupon->max_discount : null;
        $this->starts_at = $coupon->starts_at?->format('Y-m-d');
        $this->ends_at = $coupon->ends_at?->format('Y-m-d');
        $this->usage_limit = $coupon->usage_limit !== null ? (string) $coupon->usage_limit : null;
        $this->per_user_limit = $coupon->per_user_limit !== null ? (string) $coupon->per_user_limit : null;
        $this->applies_to = $coupon->applies_to->value;
        $this->applies_to_id = $coupon->applies_to_id !== null ? (string) $coupon->applies_to_id : null;
        $this->is_active = $coupon->is_active;
    }

    public function toData(): array
    {
        return [
            'code' => strtoupper(trim($this->code)),
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'min_subtotal' => $this->min_subtotal !== '' && $this->min_subtotal !== null ? $this->min_subtotal : null,
            'max_discount' => $this->max_discount !== '' && $this->max_discount !== null ? $this->max_discount : null,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'usage_limit' => $this->usage_limit !== '' && $this->usage_limit !== null ? (int) $this->usage_limit : null,
            'per_user_limit' => $this->per_user_limit !== '' && $this->per_user_limit !== null ? (int) $this->per_user_limit : null,
            'applies_to' => $this->applies_to,
            'applies_to_id' => $this->applies_to !== 'all' && $this->applies_to_id !== '' && $this->applies_to_id !== null ? (int) $this->applies_to_id : null,
            'is_active' => $this->is_active,
        ];
    }

    public function generateCode(): void
    {
        $prefixes = ['BREVARE', 'BIENVENIDO', 'PROMO', 'OFERTA'];
        $this->code = $prefixes[array_rand($prefixes)].'-'.strtoupper(Str::random(6));
    }
}
