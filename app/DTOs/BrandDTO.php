<?php

namespace App\DTOs\Catalog;

readonly class BrandDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $image,
        public bool $is_active,
        public int $sort_order,
    ) {}
}
