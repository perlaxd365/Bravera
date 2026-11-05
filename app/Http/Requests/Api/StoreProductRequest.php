<?php

namespace App\Http\Requests\Api;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $product = (array) $this->input('product', []);
        $variants = (array) $this->input('variants', []);

        if (blank($product['slug'] ?? null) && filled($product['name'] ?? null)) {
            $product['slug'] = Str::slug((string) $product['name']);
        }

        $variants = array_map(function (mixed $variant): mixed {
            if (! is_array($variant)) {
                return $variant;
            }

            $variant['images'] = array_map(function (mixed $image): mixed {
                if (! is_array($image) || filled($image['content_base64'] ?? null)) {
                    return $image;
                }

                if (filled($image['base64'] ?? null)) {
                    $image['content_base64'] = $image['base64'];
                }

                return $image;
            }, (array) ($variant['images'] ?? []));

            return $variant;
        }, $variants);

        $this->merge([
            'product' => $product,
            'variants' => $variants,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product' => ['required', 'array'],
            'product.category_path' => [
                'required',
                'string',
                'max:255',
                Rule::exists('categories', 'path')->where(
                    fn ($query) => $query->where('is_visible', true)->whereNull('deleted_at')
                ),
            ],
            'product.brand_slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('brands', 'slug')->where(
                    fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')
                ),
            ],
            'product.name' => ['required', 'string', 'max:255'],
            'product.slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug'),
            ],
            'product.short_description' => ['required', 'string', 'max:500'],
            'product.description' => ['required', 'string'],
            'product.seo_title' => ['nullable', 'string', 'max:255'],
            'product.seo_description' => ['nullable', 'string', 'max:320'],
            'product.allows_shalom_cod' => ['sometimes', 'boolean'],
            'product.is_featured' => ['sometimes', 'boolean'],
            'product.translations' => ['sometimes', 'array'],
            'product.translations.en' => ['sometimes', 'array'],
            'product.translations.en.name' => ['nullable', 'string', 'max:255'],
            'product.translations.en.short_description' => ['nullable', 'string', 'max:500'],
            'product.translations.en.description' => ['nullable', 'string'],
            'product.translations.en.seo_title' => ['nullable', 'string', 'max:255'],
            'product.translations.en.seo_description' => ['nullable', 'string', 'max:320'],

            'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*' => ['required', 'array'],
            'variants.*.sku' => [
                'required',
                'string',
                'max:100',
                'distinct',
                Rule::unique('product_variants', 'sku'),
            ],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.sale_price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'variants.*.compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:90'],
            'variants.*.wholesale_price' => [
                'nullable',
                'numeric',
                'min:0',
                'required_with:variants.*.wholesale_min_quantity',
            ],
            'variants.*.wholesale_min_quantity' => [
                'nullable',
                'integer',
                'min:2',
                'required_with:variants.*.wholesale_price',
            ],
            'variants.*.cost_price' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'variants.*.length' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'variants.*.width' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'variants.*.height' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.attributes' => ['sometimes', 'array'],
            'variants.*.attributes.*' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_scalar($value)) {
                        $fail('Cada atributo debe ser texto o un número.');
                    }
                },
            ],
            'variants.*.supplier' => ['required', 'array'],
            'variants.*.supplier.code' => [
                'required',
                'string',
                'max:20',
                Rule::exists('suppliers', 'code')->where(
                    fn ($query) => $query->where('status', 'active')->whereNull('deleted_at')
                ),
            ],
            'variants.*.supplier.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.supplier.cost_price' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.supplier.shipping_cost' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.supplier.sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.supplier.stock' => ['required', 'integer', 'min:1'],
            'variants.*.supplier.minimum_stock' => ['sometimes', 'integer', 'min:0'],
            'variants.*.supplier.estimated_dispatch_days' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'variants.*.supplier.internal_notes' => ['nullable', 'string'],

            'variants.*.images' => ['required', 'array', 'min:1', 'max:20'],
            'variants.*.images.*' => ['required', 'array'],
            'variants.*.images.*.file_name' => [
                'required',
                'string',
                'max:255',
                'regex:/\.(jpe?g|png|webp)$/i',
            ],
            'variants.*.images.*.content_base64' => ['required', 'string', 'max:7000000'],
            'variants.*.images.*.is_primary' => ['sometimes', 'boolean'],
            'variants.*.images.*.sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $variants = (array) $this->input('variants', []);
                $defaultCount = 0;

                foreach ($variants as $variantIndex => $variant) {
                    if (! is_array($variant)) {
                        continue;
                    }

                    $defaultCount += (bool) ($variant['is_default'] ?? false) ? 1 : 0;
                    $salePrice = (float) ($variant['sale_price'] ?? 0);
                    $comparePrice = $variant['compare_price'] ?? null;

                    if ($comparePrice !== null && (float) $comparePrice > 0 && (float) $comparePrice <= $salePrice) {
                        $validator->errors()->add(
                            "variants.{$variantIndex}.compare_price",
                            'compare_price debe ser mayor que sale_price cuando se informa.',
                        );
                    }

                    if (filled($variant['wholesale_price'] ?? null)
                        && (float) $variant['wholesale_price'] >= $salePrice) {
                        $validator->errors()->add(
                            "variants.{$variantIndex}.wholesale_price",
                            'El precio mayorista debe ser menor que sale_price.',
                        );
                    }

                    foreach ((array) ($variant['attributes'] ?? []) as $attributeName => $attributeValue) {
                        if (mb_strlen(trim((string) $attributeName)) > 100) {
                            $validator->errors()->add(
                                "variants.{$variantIndex}.attributes",
                                'Cada nombre de atributo puede tener como máximo 100 caracteres.',
                            );
                        }

                        if (is_scalar($attributeValue) && mb_strlen(trim((string) $attributeValue)) > 150) {
                            $validator->errors()->add(
                                "variants.{$variantIndex}.attributes.{$attributeName}",
                                'Cada valor de atributo puede tener como máximo 150 caracteres.',
                            );
                        }
                    }

                    $images = (array) ($variant['images'] ?? []);
                    $primaryCount = collect($images)
                        ->filter(fn ($image) => is_array($image) && (bool) ($image['is_primary'] ?? false))
                        ->count();

                    if ($primaryCount > 1) {
                        $validator->errors()->add(
                            "variants.{$variantIndex}.images",
                            'Solo una imagen por variante puede ser principal.',
                        );
                    }

                    foreach ($images as $imageIndex => $image) {
                        if (! is_array($image)) {
                            continue;
                        }

                        $decoded = $this->decodeBase64((string) ($image['content_base64'] ?? ''));

                        if ($decoded === null) {
                            $validator->errors()->add(
                                "variants.{$variantIndex}.images.{$imageIndex}.content_base64",
                                'La imagen no es Base64 válido.',
                            );

                            continue;
                        }

                        if (strlen($decoded) > self::MAX_IMAGE_BYTES) {
                            $validator->errors()->add(
                                "variants.{$variantIndex}.images.{$imageIndex}.content_base64",
                                'Cada imagen puede pesar como máximo 5 MB.',
                            );

                            continue;
                        }

                        $dimensions = @getimagesizefromstring($decoded);
                        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

                        if ($dimensions === false || ! in_array($dimensions['mime'] ?? '', $allowedMimeTypes, true)) {
                            $validator->errors()->add(
                                "variants.{$variantIndex}.images.{$imageIndex}.content_base64",
                                'La imagen debe ser JPG, PNG o WebP válida.',
                            );
                        }
                    }
                }

                if ($defaultCount > 1) {
                    $validator->errors()->add(
                        'variants',
                        'Solo una variante puede marcarse como predeterminada.',
                    );
                }
            },
        ];
    }

    private function decodeBase64(string $value): ?string
    {
        if (preg_match('/^data:image\/[a-z0-9.+-]+;base64,(.+)$/is', $value, $matches)) {
            $value = $matches[1];
        }

        $decoded = base64_decode(preg_replace('/\s+/', '', $value) ?? '', true);

        return $decoded === false || $decoded === '' ? null : $decoded;
    }
}
