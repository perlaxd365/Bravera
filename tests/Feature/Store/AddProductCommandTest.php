<?php

namespace Tests\Feature\Store;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\CloudinaryImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AddProductCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_producto_con_imagenes_precio_mayorista_y_contra_entrega(): void
    {
        $category = Category::create([
            'name' => 'Decoración',
            'slug' => 'decoracion',
            'path' => 'navidad/decoracion',
            'status' => true,
        ]);

        Brand::create([
            'name' => 'Casa Joven',
            'slug' => 'casa-joven',
            'is_active' => true,
        ]);

        Supplier::firstOrCreate(
            ['code' => 'BREVARE'],
            ['business_name' => 'Brevare', 'status' => 'active'],
        );

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'brevare-product-test-'.uniqid();
        mkdir($directory);

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        );

        file_put_contents($directory.DIRECTORY_SEPARATOR.'foto-1.png', $png);
        file_put_contents($directory.DIRECTORY_SEPARATOR.'foto-2.png', $png);

        $json = [
            'product' => [
                'category_path' => $category->path,
                'brand_slug' => 'casa-joven',
                'name' => 'Guirnalda de prueba',
                'slug' => 'guirnalda-de-prueba',
                'short_description' => 'Guirnalda LED de prueba.',
                'description' => 'Descripción de prueba.',
                'allows_shalom_cod' => true,
            ],
            'variants' => [[
                'sku' => 'BVR-TEST-GUIR-3D',
                'sale_price' => 38.90,
                'wholesale_price' => 35.90,
                'wholesale_min_quantity' => 3,
                'attributes' => ['Largo' => '3 m'],
                'supplier' => [
                    'code' => 'BREVARE',
                    'stock' => 50,
                    'estimated_dispatch_days' => 1,
                ],
                'images' => [
                    ['path' => 'foto-1.png', 'is_primary' => true],
                    ['path' => 'foto-2.png'],
                ],
            ]],
        ];

        $jsonPath = $directory.DIRECTORY_SEPARATOR.'product.json';
        file_put_contents($jsonPath, json_encode($json));

        $cloudinary = Mockery::mock(CloudinaryImageService::class);
        $cloudinary->shouldReceive('upload')
            ->twice()
            ->andReturnUsing(function ($file) {
                $name = $file->getClientOriginalName();

                return [
                    'public_id' => 'productos/'.$name,
                    'url' => 'http://res.cloudinary.com/demo/image/upload/productos/'.$name,
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/productos/'.$name,
                    'file_name' => $name,
                    'format' => 'png',
                    'size' => 100,
                    'width' => 1,
                    'height' => 1,
                ];
            });
        $cloudinary->shouldReceive('delete')->zeroOrMoreTimes();
        $this->app->instance(CloudinaryImageService::class, $cloudinary);

        $this->artisan('brevare:add-product', [
            'json' => $jsonPath,
            '--images-dir' => $directory,
        ])->assertSuccessful();

        $product = Product::where('slug', 'guirnalda-de-prueba')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $this->assertTrue($product->allows_shalom_cod);
        $this->assertSame(38.90, (float) $variant->sale_price);
        $this->assertSame(35.90, (float) $variant->wholesale_price);
        $this->assertSame(3, (int) $variant->wholesale_min_quantity);
        $this->assertSame(2, $variant->images()->count());
        $this->assertSame(50, (int) $variant->supplierVariants()->firstOrFail()->stock);
    }
}
