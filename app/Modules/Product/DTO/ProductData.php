<?php

namespace App\Modules\Product\DTO;

use App\Models\Product;

readonly class ProductData
{
    public function __construct(
        public ?int $id,
        public int $category_id,
        public ?int $brand_id,
        public string $name,
        public string $slug,
        public ?string $short_description,
        public ?string $description,
        public bool $status,
        public bool $is_featured,
        public bool $is_visible,
        public ?string $seo_title,
        public ?string $seo_description,
    ) {}

    /**
     * Crear DTO desde un array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            category_id: (int) $data['category_id'],
            brand_id: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            name: trim($data['name']),
            slug: trim($data['slug']),
            short_description: $data['short_description'] ?? null,
            description: $data['description'] ?? null,
            status: (bool) ($data['status'] ?? true),
            is_featured: (bool) ($data['is_featured'] ?? false),
            is_visible: (bool) ($data['is_visible'] ?? true),
            seo_title: $data['seo_title'] ?? null,
            seo_description: $data['seo_description'] ?? null,
        );
    }

    /**
     * Crear DTO desde un modelo.
     */
    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            category_id: $product->category_id,
            brand_id: $product->brand_id,
            name: $product->name,
            slug: $product->slug,
            short_description: $product->short_description,
            description: $product->description,
            status: $product->status,
            is_featured: $product->is_featured,
            is_visible: $product->is_visible,
            seo_title: $product->seo_title,
            seo_description: $product->seo_description,
        );
    }

    /**
     * Convertir el DTO a array.
     */
    public function toArray(): array
    {
        return [
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_visible' => $this->is_visible,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }
}
