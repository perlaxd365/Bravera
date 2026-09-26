<?php

namespace App\Enums;

enum CouponAppliesTo: string
{
    case ALL = 'all';
    case SUPPLIER = 'supplier';
    case PRODUCT = 'product';
    case CATEGORY = 'category';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Todo el catálogo',
            self::SUPPLIER => 'Solo un proveedor',
            self::PRODUCT => 'Solo un producto',
            self::CATEGORY => 'Solo una categoría',
        };
    }
}
