<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AddProductFromJson extends Command
{
    protected $signature = 'brevare:add-product
        {json : Ruta del JSON con el producto}
        {--images-dir= : Carpeta base para las imágenes indicadas en el JSON}';

    protected $description = 'Crea un producto Brevare con variantes, precios, imágenes Cloudinary y modalidades de envío';

    public function handle(CloudinaryImageService $cloudinary): int
    {
        $path = (string) $this->argument('json');

        if (! is_file($path)) {
            $this->error("No existe el archivo JSON: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload) || ! is_array($payload['product'] ?? null)) {
            $this->error('El JSON no tiene la estructura esperada: falta la clave "product".');

            return self::FAILURE;
        }

        $productData = $payload['product'];
        $variants = $payload['variants'] ?? [];
        $imagesDir = (string) ($this->option('images-dir') ?: dirname($path));

        $category = Category::query()
            ->where('path', (string) ($productData['category_path'] ?? ''))
            ->first();

        if (! $category) {
            $this->error('No existe la categoría indicada en category_path.');

            return self::FAILURE;
        }

        $brand = null;

        if (filled($productData['brand_slug'] ?? null)) {
            $brand = Brand::query()->where('slug', (string) $productData['brand_slug'])->first();

            if (! $brand) {
                $this->error('No existe la marca indicada en brand_slug.');

                return self::FAILURE;
            }
        }

        if (! is_array($variants) || $variants === []) {
            $this->error('El producto necesita al menos una variante.');

            return self::FAILURE;
        }

        $slug = Str::slug((string) ($productData['slug'] ?? $productData['name'] ?? ''));

        if ($slug === '') {
            $this->error('El producto necesita name o slug.');

            return self::FAILURE;
        }

        if (Product::withTrashed()->where('slug', $slug)->exists()) {
            $this->error("Ya existe un producto con el slug {$slug}. No se sobrescribió nada.");

            return self::FAILURE;
        }

        $uploadedPublicIds = [];

        try {
            $product = DB::transaction(function () use (
                $productData,
                $variants,
                $imagesDir,
                $category,
                $brand,
                $slug,
                $cloudinary,
                &$uploadedPublicIds
            ) {
                $name = trim((string) $productData['name']);

                $product = Product::create([
                    'category_id' => $category->id,
                    'brand_id' => $brand?->id,
                    'name' => $name,
                    'slug' => $slug,
                    'short_description' => $productData['short_description'] ?? null,
                    'description' => $productData['description'] ?? null,
                    'status' => (bool) ($productData['status'] ?? true),
                    'is_featured' => (bool) ($productData['is_featured'] ?? false),
                    'is_visible' => (bool) ($productData['is_visible'] ?? true),
                    'allows_shalom_cod' => (bool) ($productData['allows_shalom_cod'] ?? false),
                    'seo_title' => $productData['seo_title'] ?? ($name.' | Brevare'),
                    'seo_description' => $productData['seo_description']
                        ?? Str::limit(strip_tags((string) ($productData['short_description'] ?? $productData['description'] ?? '')), 160),
                ]);

                foreach ($variants as $variantData) {
                    if (! is_array($variantData)) {
                        throw new \RuntimeException('Cada variante debe ser un objeto JSON.');
                    }

                    $sku = trim((string) ($variantData['sku'] ?? ''));

                    if ($sku === '') {
                        throw new \RuntimeException('Cada variante necesita un SKU.');
                    }

                    if (ProductVariant::withTrashed()->where('sku', $sku)->exists()) {
                        throw new \RuntimeException("El SKU {$sku} ya existe.");
                    }

                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'barcode' => $variantData['barcode'] ?? null,
                        'cost_price' => (float) ($variantData['cost_price'] ?? 0),
                        'sale_price' => (float) ($variantData['sale_price'] ?? 0),
                        'compare_price' => $variantData['compare_price'] ?? null,
                        'discount_percent' => (float) ($variantData['discount_percent'] ?? 0),
                        'wholesale_price' => $variantData['wholesale_price'] ?? null,
                        'wholesale_min_quantity' => (int) ($variantData['wholesale_min_quantity'] ?? 0),
                        'weight' => (float) ($variantData['weight'] ?? 0),
                        'length' => (float) ($variantData['length'] ?? 0),
                        'width' => (float) ($variantData['width'] ?? 0),
                        'height' => (float) ($variantData['height'] ?? 0),
                        'is_default' => (bool) ($variantData['is_default'] ?? true),
                        'is_active' => (bool) ($variantData['is_active'] ?? true),
                    ]);

                    $this->attachAttributes($variant, $variantData['attributes'] ?? []);
                    $this->createSupplierVariant($variant, $variantData['supplier'] ?? []);
                    $this->createImages($product, $variant, $variantData['images'] ?? [], $imagesDir, $cloudinary, $uploadedPublicIds);
                }

                return $product;
            });
        } catch (Throwable $e) {
            foreach ($uploadedPublicIds as $publicId) {
                try {
                    $cloudinary->delete($publicId);
                } catch (Throwable) {
                    // La transacción ya se revirtió; no ocultamos el error original.
                }
            }

            report($e);
            $this->error('No se pudo crear el producto: '.$e->getMessage());

            return self::FAILURE;
        }

        $product->load(['variants.images', 'variants.supplierVariants.supplier']);

        $this->info('Producto creado correctamente.');
        $this->line('ID: '.$product->id);
        $this->line('URL: '.route('store.product', ['slug' => $product->slug]));
        $this->line('Contra entrega Shalom: '.($product->allows_shalom_cod ? 'sí' : 'no'));

        foreach ($product->variants as $variant) {
            $this->line(sprintf(
                'SKU %s · S/ %.2f · stock %d · imágenes %d',
                $variant->sku,
                (float) $variant->sale_price,
                (int) $variant->supplierVariants->sum(fn ($supplierVariant) => $supplierVariant->availableStock()),
                $variant->images->count(),
            ));
        }

        return self::SUCCESS;
    }

    private function attachAttributes(ProductVariant $variant, array $attributes): void
    {
        foreach ($attributes as $name => $value) {
            $name = trim((string) $name);
            $value = trim((string) $value);

            if ($name === '' || $value === '') {
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

    private function createSupplierVariant(ProductVariant $variant, array $supplierData): void
    {
        $code = trim((string) ($supplierData['code'] ?? 'BREVARE'));
        $supplier = Supplier::query()->where('code', $code)->first();

        if (! $supplier) {
            throw new \RuntimeException("No existe el proveedor {$code}.");
        }

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'supplier_sku' => $supplierData['sku'] ?? $variant->sku,
            'cost_price' => (float) ($supplierData['cost_price'] ?? 0),
            'shipping_cost' => (float) ($supplierData['shipping_cost'] ?? 0),
            'stock' => max(0, (int) ($supplierData['stock'] ?? 0)),
            'reserved_stock' => 0,
            'minimum_stock' => max(0, (int) ($supplierData['minimum_stock'] ?? 0)),
            'estimated_dispatch_days' => max(0, (int) ($supplierData['estimated_dispatch_days'] ?? 0)),
            'is_default' => (bool) ($supplierData['is_default'] ?? true),
            'is_active' => (bool) ($supplierData['is_active'] ?? true),
        ]);
    }

    private function createImages(
        Product $product,
        ProductVariant $variant,
        array $images,
        string $imagesDir,
        CloudinaryImageService $cloudinary,
        array &$uploadedPublicIds,
    ): void {
        if ($images === []) {
            throw new \RuntimeException('Cada variante necesita al menos una imagen.');
        }

        $hasPrimary = collect($images)->contains(fn ($image) => (bool) ($image['is_primary'] ?? false));

        foreach (array_values($images) as $index => $image) {
            $relativePath = is_string($image) ? $image : (string) ($image['path'] ?? '');

            if ($relativePath === '') {
                continue;
            }

            $absolutePath = str_starts_with($relativePath, DIRECTORY_SEPARATOR)
                || preg_match('/^[A-Za-z]:[\\\\\\/]/', $relativePath)
                ? $relativePath
                : rtrim($imagesDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath;

            if (! is_file($absolutePath)) {
                throw new \RuntimeException("No existe la imagen: {$absolutePath}");
            }

            $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
            $uploaded = $cloudinary->upload(
                new UploadedFile($absolutePath, basename($absolutePath), $mime, null, true),
                CloudinaryImageService::FOLDER_PRODUCTS,
            );

            $uploadedPublicIds[] = $uploaded['public_id'];

            ProductImage::create([
                'product_variant_id' => $variant->id,
                'public_id' => $uploaded['public_id'],
                'file_name' => $uploaded['file_name'],
                'url' => $uploaded['url'],
                'secure_url' => $uploaded['secure_url'],
                'format' => $uploaded['format'],
                'size' => $uploaded['size'],
                'width' => $uploaded['width'],
                'height' => $uploaded['height'],
                'alt' => $product->name,
                'is_primary' => is_array($image)
                    ? (bool) ($image['is_primary'] ?? false)
                    : (! $hasPrimary && $index === 0),
                'sort_order' => is_array($image)
                    ? (int) ($image['sort_order'] ?? $index + 1)
                    : $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
