<?php

namespace App\Enums;

enum ProductType: string
{
    case PHYSICAL = 'physical';
    case COURSE = 'course';

    public function label(): string
    {
        return match ($this) {
            self::PHYSICAL => 'Producto físico',
            self::COURSE => 'Curso digital',
        };
    }
}
