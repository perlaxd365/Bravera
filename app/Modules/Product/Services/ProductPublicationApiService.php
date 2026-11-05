<?php

namespace App\Modules\Product\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Services\CloudinaryImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProductPublicationApiService
{
    public function __construct(
        private readonly CloudinaryImageService $cloudinary,
        private readonly ProductReadinessService $readiness,
        private readonly ProductTranslationService $translations,
    ) {}

    /**
     * Publica un producto físico con sus variantes, imágenes, atributos,
     * inventario de proveedor y traducciones en una sola operación.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(array $payload): Product
    {
        $uploads = [];

        try {
            $uploads = $this->uploadImages((array) $payload['variants']);

            $product = DB::transaction(function () use ($payload, $uploads): Product {
                return $this->createProduct($payload, $uploads);
            });
        } catch (Throwable $exception) {
            $this->deleteUploads($uploads);

            throw $exception;
        }

        return $product->load($this->relations());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<int, array<string, mixed>>>  $uploads
     */
    private function createProduct(array $payload, array $uploads): Product
    {
        $productData = (array) $payload['product'];
        $variants = (array) $payload['variants'];
        $category = Category::query()
            ->where('path', (string) $productData['category_path'])
            ->where('is_visible', true)
            ->firstOrFail();

        $brand = filled($productData['brand_slug'] ?? null)
            ? Brand::query()
                ->where('slug', (string) $productData['brand_slug'])
                ->where('is_active', true)
                ->firstOrFail()
            : null;

        $name = trim((string) $productData['name']);
        $shortDescription = trim((string) $productData['short_description']);
        $seoDescription = trim((string) ($productData['seo_description'] ?? ''));

        if ($seoDescription === '') {
            $seoDescription = Str::limit(strip_tags($shortDescription), 160, '');
        }

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'product_type' => 'physical',
            'name' => $name,
            'slug' => (string) $productData['slug'],
            'short_description' => $shortDescription,
            'description' => (string) $productData['description'],
            'status' => true,
            'is_featured' => (bool) ($productData['is_featured'] ?? false),
            'is_visible' => true,
            'allows_shalom_cod' => (bool) ($productData['allows_shalom_cod'] ?? false),
            'seo_title' => filled($productData['seo_title'] ?? null)
                ? (string) $productData['seo_title']
                : $name.' | Brevare',
            'seo_description' => $seoDescription,
        ]);

        $this->translations->sync($product, (array) ($productData['translations'] ?? []));

        $hasDefaultVariant = collect($variants)
            ->contains(fn ($variant) => is_array($variant) && (bool) ($variant['is_default'] ?? false));

        foreach (array_values($variants) as $variantIndex => $variantData) {
            $supplier = Supplier::query()
                ->where('code', (string) $variantData['supplier']['code'])
                ->where('status', 'active')
                ->firstOrFail();

            $salePrice = (float) $variantData['sale_price'];
            $comparePrice = filled($variantData['compare_price'] ?? null)
                ? (float) $variantData['compare_price']
                : null;
            $discountPercent = $comparePrice !== null && $comparePrice > $salePrice
                ? round((1 - ($salePrice / $comparePrice)) * 100, 2)
                : min(90, max(0, (float) ($variantData['discount_percent'] ?? 0)));

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => trim((string) $variantData['sku']),
                'barcode' => $variantData['barcode'] ?? null,
                'cost_price' => (float) ($variantData['cost_price'] ?? 0),
                'sale_price' => $salePrice,
                'compare_price' => $comparePrice,
                'discount_percent' => $discountPercent,
                'wholesale_price' => filled($variantData['wholesale_price'] ?? null)
                    ? (float) $variantData['wholesale_price']
                    : null,
                'wholesale_min_quantity' => filled($variantData['wholesale_min_quantity'] ?? null)
                    ? (int) $variantData['wholesale_min_quantity']
                    : null,
                'weight' => $variantData['weight'] ?? null,
                'length' => $variantData['length'] ?? null,
                'width' => $variantData['width'] ?? null,
                'height' => $variantData['height'] ?? null,
                'sync_enabled' => true,
                'is_default' => (bool) ($variantData['is_default'] ?? (! $hasDefaultVariant && $variantIndex === 0)),
                'is_active' => true,
            ]);

            $this->attachAttributes($variant, (array) ($variantData['attributes'] ?? []));

            SupplierVariant::create([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $variant->id,
                'supplier_sku' => $variantData['supplier']['sku'] ?? $variant->sku,
                'cost_price' => (float) ($variantData['supplier']['cost_price'] ?? $variantData['cost_price'] ?? 0),
                'shipping_cost' => (float) ($variantData['supplier']['shipping_cost'] ?? 0),
                'supplier_sale_price' => filled($variantData['supplier']['sale_price'] ?? null)
                    ? (float) $variantData['supplier']['sale_price']
                    : $salePrice,
                'stock' => max(1, (int) $variantData['supplier']['stock']),
                'reserved_stock' => 0,
                'minimum_stock' => max(0, (int) ($variantData['supplier']['minimum_stock'] ?? 0)),
                'estimated_dispatch_days' => max(
                    0,
                    (int) ($variantData['supplier']['estimated_dispatch_days'] ?? $supplier->estimated_dispatch_days),
                ),
                'is_default' => true,
                'is_active' => true,
                'internal_notes' => $variantData['supplier']['internal_notes'] ?? null,
            ]);

            $hasPrimaryImage = collect($variantData['images'])
                ->contains(fn ($image) => (bool) ($image['is_primary'] ?? false));

            foreach (array_values($variantData['images']) as $imageIndex => $imageData) {
                $upload = $uploads[$variantIndex][$imageIndex];

                ProductImage::create([
                    'product_variant_id' => $variant->id,
                    'public_id' => $upload['public_id'],
                    'file_name' => $upload['file_name'],
                    'url' => $upload['url'],
                    'secure_url' => $upload['secure_url'],
                    'format' => $upload['format'],
                    'size' => $upload['size'],
                    'width' => $upload['width'],
                    'height' => $upload['height'],
                    'alt' => $name,
                    'is_primary' => (bool) ($imageData['is_primary'] ?? (! $hasPrimaryImage && $imageIndex === 0)),
                    'sort_order' => (int) ($imageData['sort_order'] ?? $imageIndex + 1),
                    'is_active' => true,
                ]);
            }
        }

        $product = $product->fresh($this->relations());
        $assessment = $this->readiness->assess($product);

        if (! $assessment['ready']) {
            throw ValidationException::withMessages([
                'product' => ['No se puede publicar todavía. Falta: '.implode(', ', $assessment['missing']).'.'],
            ]);
        }

        return $product;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function uploadImages(array $variants): array
    {
        $uploads = [];

        foreach (array_values($variants) as $variantIndex => $variant) {
            foreach (array_values((array) $variant['images']) as $imageIndex => $image) {
                $binary = $this->decodeBase64((string) $image['content_base64']);
                $dimensions = $binary !== null ? @getimagesizefromstring($binary) : false;

                if ($binary === null || $dimensions === false) {
                    throw ValidationException::withMessages([
                        "variants.{$variantIndex}.images.{$imageIndex}.content_base64" => [
                            'La imagen no contiene un JPG, PNG o WebP válido.',
                        ],
                    ]);
                }

                $temporaryPath = tempnam(sys_get_temp_dir(), 'brevare-product-api-');

                if ($temporaryPath === false) {
                    throw new RuntimeException('No se pudo preparar la imagen temporal.');
                }

                try {
                    file_put_contents($temporaryPath, $binary);
                    $mime = (string) ($dimensions['mime'] ?? 'image/jpeg');
                    $uploadedFile = new UploadedFile(
                        $temporaryPath,
                        (string) $image['file_name'],
                        $mime,
                        null,
                        true,
                    );

                    try {
                        $uploads[$variantIndex][$imageIndex] = $this->cloudinary->upload(
                            $uploadedFile,
                            CloudinaryImageService::FOLDER_PRODUCTS,
                        );
                    } catch (Throwable $exception) {
                        throw new RuntimeException('No se pudo subir una imagen a Cloudinary.', 0, $exception);
                    }
                } finally {
                    @unlink($temporaryPath);
                }
            }
        }

        return $uploads;
    }

    private function attachAttributes(ProductVariant $variant, array $attributes): void
    {
        foreach ($attributes as $name => $values) {
            $name = trim((string) $name);
            $values = is_array($values) ? $values : [$values];

            if ($name === '') {
                continue;
            }

            $attribute = Attribute::withTrashed()->firstOrNew(['slug' => Str::slug($name)]);
            $attribute->fill([
                'name' => $name,
                'type' => 'select',
                'is_filter' => true,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => $attribute->sort_order ?: 0,
            ]);

            if ($attribute->trashed()) {
                $attribute->restore();
            }

            $attribute->save();

            $seenValues = [];

            foreach ($values as $value) {
                if (! is_scalar($value)) {
                    continue;
                }

                $value = trim((string) $value);

                if ($value === '' || isset($seenValues[$value])) {
                    continue;
                }

                $seenValues[$value] = true;

                $attributeValue = AttributeValue::withTrashed()->firstOrNew([
                    'attribute_id' => $attribute->id,
                    'slug' => Str::slug($value),
                ]);
                $attributeValue->fill([
                    'value' => $value,
                    'is_active' => true,
                ]);

                if ($attributeValue->trashed()) {
                    $attributeValue->restore();
                }

                $attributeValue->save();

                ProductVariantAttributeValue::create([
                    'product_variant_id' => $variant->id,
                    'attribute_id' => $attribute->id,
                    'attribute_value_id' => $attributeValue->id,
                ]);
            }
        }
    }

    private function decodeBase64(string $value): ?string
    {
        if (preg_match('/^data:image\/[a-z0-9.+-]+;base64,(.+)$/is', $value, $matches)) {
            $value = $matches[1];
        }

        $decoded = base64_decode(preg_replace('/\s+/', '', $value) ?? '', true);

        return $decoded === false || $decoded === '' ? null : $decoded;
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $uploads
     */
    private function deleteUploads(array $uploads): void
    {
        foreach ($uploads as $variantUploads) {
            foreach ($variantUploads as $upload) {
                try {
                    $this->cloudinary->delete($upload['public_id'] ?? null);
                } catch (Throwable) {
                    // Conservamos el error original de la publicación.
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'category',
            'brand',
            'translations',
            'variants.images',
            'variants.attributeValues.attribute',
            'variants.attributeValues.value',
            'variants.supplierVariants.supplier',
        ];
    }
}
