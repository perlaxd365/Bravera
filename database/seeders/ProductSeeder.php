<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Imágenes reales (CDN de Pexels) por producto y color de variante.
     * Clave: slug del producto => label del color ('default' si no aplica).
     */
    public const REAL_IMAGES = [
        'smartphone-samsung-galaxy-a24' => [
            'Negro' => [
                'https://images.pexels.com/photos/28687649/pexels-photo-28687649/free-photo-of-modern-samsung-smartphone-in-low-light-setting.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/30466739/pexels-photo-30466739/free-photo-of-elegant-smartphone-on-minimalist-shelf.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/22604142/pexels-photo-22604142/free-photo-of-new-samsung-smartphone.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Azul' => [
                'https://images.pexels.com/photos/30466736/pexels-photo-30466736/free-photo-of-hand-holding-samsung-galaxy-s25-ultra-over-box.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/28902919/pexels-photo-28902919/free-photo-of-four-smartphones-on-display-showing-various-screens.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'redmi-note-12-pro' => [
            'Azul' => [
                'https://images.pexels.com/photos/16149968/pexels-photo-16149968/free-photo-of-get-ready-for-the-future-with-samsung-galaxy-s23.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/28902919/pexels-photo-28902919/free-photo-of-four-smartphones-on-display-showing-various-screens.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'laptop-lenovo-ideapad-3' => [
            'default' => [
                'https://images.pexels.com/photos/16518045/pexels-photo-16518045/free-photo-of-person-typing-on-keyboard-of-silver-laptop.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/16645416/pexels-photo-16645416/free-photo-of-opened-silver-laptop-and-black-computer-mouse.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/11129922/pexels-photo-11129922.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'tableta-dibujo-tabletas' => [
            'default' => [
                'https://images.pexels.com/photos/16313709/pexels-photo-16313709/free-photo-of-wacom-intuos-pro-m-next-to-laptop-on-desk.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/29940157/pexels-photo-29940157/free-photo-of-digital-artist-using-graphic-tablet-with-stylus.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/16313504/pexels-photo-16313504/free-photo-of-graphic-designer-drawing-on-tablet.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'polo-manga-corta-basico' => [
            'Negro' => [
                'https://images.pexels.com/photos/27328545/pexels-photo-27328545/free-photo-of-a-man-in-a-polo-shirt-standing-on-a-foggy-hill.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/26063373/pexels-photo-26063373/free-photo-of-man-in-eyeglasses-and-polo-shirt.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Rojo' => [
                'https://images.pexels.com/photos/36669383/pexels-photo-36669383/free-photo-of-stylish-man-in-red-t-shirt-with-shadow-play.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'juego-ollas-antiadherentes' => [
            'default' => [
                'https://images.pexels.com/photos/28726706/pexels-photo-28726706/free-photo-of-stack-of-stainless-steel-cooking-pots-in-kitchen.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/30981355/pexels-photo-30981355/free-photo-of-modern-kitchenware-setup-with-stainless-steel-pots.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/24989144/pexels-photo-24989144/free-photo-of-traditional-jars-on-a-stove.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'smartphone-apple-iphone-13' => [
            'Negro' => [
                'https://images.pexels.com/photos/818043/pexels-photo-818043.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/18311092/pexels-photo-18311092.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Blanco' => [
                'https://images.pexels.com/photos/3945672/pexels-photo-3945672.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'xiaomi-poco-x5-pro' => [
            'Azul' => [
                'https://images.pexels.com/photos/33915434/pexels-photo-33915434.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/32255295/pexels-photo-32255295.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/21854468/pexels-photo-21854468.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'laptop-hp-pavilion-15' => [
            'default' => [
                'https://images.pexels.com/photos/93405/pexels-photo-93405.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/15940010/pexels-photo-15940010.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/3747070/pexels-photo-3747070.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'tablet-samsung-galaxy-tab-a9' => [
            'default' => [
                'https://images.pexels.com/photos/218717/pexels-photo-218717.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/286565/pexels-photo-286565.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/283939/pexels-photo-283939.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/13570165/pexels-photo-13570165.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'jeans-slim-hombre' => [
            'Azul' => [
                'https://images.pexels.com/photos/10133278/pexels-photo-10133278.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/4546763/pexels-photo-4546763.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Negro' => [
                'https://images.pexels.com/photos/4109798/pexels-photo-4109798.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'camisa-manga-larga-hombre' => [
            'Blanco' => [
                'https://images.pexels.com/photos/9587148/pexels-photo-9587148.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Azul' => [
                'https://images.pexels.com/photos/4443831/pexels-photo-4443831.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/35171075/pexels-photo-35171075.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'vestido-estampado-verano' => [
            'Azul' => [
                'https://images.pexels.com/photos/29017408/pexels-photo-29017408.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Blanco' => [
                'https://images.pexels.com/photos/6181955/pexels-photo-6181955.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Rojo' => [
                'https://images.pexels.com/photos/20577874/pexels-photo-20577874.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'blazer-femenino-clasico' => [
            'Negro' => [
                'https://images.pexels.com/photos/19133986/pexels-photo-19133986.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/19303745/pexels-photo-19303745.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
            'Beige' => [
                'https://images.pexels.com/photos/11124953/pexels-photo-11124953.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'bolso-cuero-mujer' => [
            'default' => [
                'https://images.pexels.com/photos/22432991/pexels-photo-22432991.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/9327162/pexels-photo-9327162.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/36367484/pexels-photo-36367484.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'set-cuchillos-chef-acero' => [
            'default' => [
                'https://images.pexels.com/photos/16443132/pexels-photo-16443132.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/16457340/pexels-photo-16457340.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/4226864/pexels-photo-4226864.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'batidora-mano-5-velocidades' => [
            'default' => [
                'https://images.pexels.com/photos/6605163/pexels-photo-6605163.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/8357682/pexels-photo-8357682.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/6802636/pexels-photo-6802636.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'juego-sabanas-algodon-4p' => [
            'default' => [
                'https://images.pexels.com/photos/38651677/pexels-photo-38651677.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/28513849/pexels-photo-28513849.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/22711513/pexels-photo-22711513.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
        'acolchado-queen-algodon' => [
            'default' => [
                'https://images.pexels.com/photos/20801059/pexels-photo-20801059.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/7765000/pexels-photo-7765000.jpeg?auto=compress&cs=tinysrgb&w=800',
                'https://images.pexels.com/photos/15410912/pexels-photo-15410912.jpeg?auto=compress&cs=tinysrgb&w=800',
            ],
        ],
    ];

    /**
     * Devuelve el conjunto de imágenes reales para un producto/color.
     */
    public static function imageSetFor(string $productSlug, string $key = 'default'): array
    {
        $set = self::REAL_IMAGES[$productSlug] ?? [];

        return $set[$key]
            ?? $set['default']
            ?? ($set === [] ? [] : $set[array_key_first($set)]);
    }

    /**
     * Ejecutar el seeder.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->seedCatalog();

            DB::commit();

            $this->command->info('✅ Catálogo de demostración creado.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->command->error('❌ Error al crear el catálogo.');
            $this->command->error($e->getMessage());

            throw $e;
        }
    }

    private function seedCatalog(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Proveedores
        |--------------------------------------------------------------------------
        */

        $supplierA = Supplier::firstOrCreate(
            ['code' => 'SUP000001'],
            [
                'business_name' => 'Distribuciones Andinas S.A.C.',
                'trade_name' => 'Distribuciones Andinas',
                'tax_id' => '20100123456',
                'email' => 'ventas@distribucionesandinas.pe',
                'phone' => '+51 987 654 321',
                'estimated_dispatch_days' => 3,
                'status' => 'active',
            ],
        );

        $supplierB = Supplier::firstOrCreate(
            ['code' => 'SUP000002'],
            [
                'business_name' => 'Importaciones Lima Norte S.A.C.',
                'trade_name' => 'ILN Import',
                'tax_id' => '20500987654',
                'email' => 'hola@ilnimport.pe',
                'phone' => '+51 912 345 678',
                'estimated_dispatch_days' => 5,
                'status' => 'active',
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | Marcas
        |--------------------------------------------------------------------------
        */

        $samsung = Brand::firstOrCreate(['slug' => 'samsung'], ['name' => 'Samsung', 'is_active' => true]);
        $lenovo = Brand::firstOrCreate(['slug' => 'lenovo'], ['name' => 'Lenovo', 'is_active' => true]);
        $xiaomi = Brand::firstOrCreate(['slug' => 'xiaomi'], ['name' => 'Xiaomi', 'is_active' => true]);
        $hp = Brand::firstOrCreate(['slug' => 'hp'], ['name' => 'HP', 'is_active' => true]);
        $apple = Brand::firstOrCreate(['slug' => 'apple'], ['name' => 'Apple', 'is_active' => true]);
        $generic = Brand::firstOrCreate(['slug' => 'generico'], ['name' => 'Genérico', 'is_active' => true]);

        /*
        |--------------------------------------------------------------------------
        | Atributos
        |--------------------------------------------------------------------------
        */

        $color = Attribute::firstOrCreate(
            ['slug' => 'color'],
            [
                'name' => 'Color',
                'type' => 'color',
                'is_filter' => true,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $talla = Attribute::firstOrCreate(
            ['slug' => 'talla'],
            [
                'name' => 'Talla',
                'type' => 'select',
                'is_filter' => true,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $black = $this->createAttributeValue($color, 'Negro', '#000000', 1);
        $blue = $this->createAttributeValue($color, 'Azul', '#2563eb', 2);
        $red = $this->createAttributeValue($color, 'Rojo', '#dc2626', 3);
        $white = $this->createAttributeValue($color, 'Blanco', '#ffffff', 4);
        $beige = $this->createAttributeValue($color, 'Beige', '#d6c7a1', 5);

        $tallaS = $this->createAttributeValue($talla, 'S', null, 1);
        $tallaM = $this->createAttributeValue($talla, 'M', null, 2);
        $tallaL = $this->createAttributeValue($talla, 'L', null, 3);

        /*
        |--------------------------------------------------------------------------
        | Categorías
        |--------------------------------------------------------------------------
        */

        $celulares = Category::where('slug', 'celulares')->first();
        $laptops = Category::where('slug', 'laptops')->first();
        $tablets = Category::where('slug', 'tablets')->first();
        $hombre = Category::where('slug', 'hombre')->first();
        $mujer = Category::where('slug', 'mujer')->first();
        $cocina = Category::where('slug', 'cocina')->first();
        $dormitorio = Category::where('slug', 'dormitorio')->first();

        /*
        |--------------------------------------------------------------------------
        | Productos
        |--------------------------------------------------------------------------
        */

        $this->createPhone(
            category: $celulares,
            brand: $samsung,
            name: 'Smartphone Samsung Galaxy A24',
            slug: 'smartphone-samsung-galaxy-a24',
            salePrices: [1099, 1099],
            comparePrice: 1299,
            cost: 820,
            colors: [
                ['name' => 'Negro', 'value' => $black],
                ['name' => 'Azul', 'value' => $blue],
            ],
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 40, 'shipping' => 12, 'dispatch' => 2],
                ['supplier' => $supplierB, 'stock' => 25, 'shipping' => 15, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['smartphone-samsung-galaxy-a24'],
        );

        $this->createPhone(
            category: $celulares,
            brand: $xiaomi,
            name: 'Redmi Note 12 Pro',
            slug: 'redmi-note-12-pro',
            salePrices: [999],
            comparePrice: 1199,
            cost: 740,
            colors: [
                ['name' => 'Azul', 'value' => $blue],
            ],
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 30, 'shipping' => 10, 'dispatch' => 3],
            ],
            images: self::REAL_IMAGES['redmi-note-12-pro'],
        );

        $this->createSimpleProduct(
            category: $laptops,
            brand: $lenovo,
            name: 'Laptop Lenovo IdeaPad 3',
            slug: 'laptop-lenovo-ideapad-3',
            short: 'Laptop con procesador Intel Core i5 y 16GB de RAM.',
            description: 'Laptop Lenovo IdeaPad 3 con pantalla de 15.6", procesador Intel Core i5 de 12.ª generación, 16GB RAM y SSD de 512GB.',
            salePrice: 2799,
            comparePrice: 3299,
            cost: 2150,
            sku: 'LEN-IP3-I5-16',
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 12, 'shipping' => 25, 'dispatch' => 3],
                ['supplier' => $supplierB, 'stock' => 8, 'shipping' => 30, 'dispatch' => 5],
            ],
            images: self::REAL_IMAGES['laptop-lenovo-ideapad-3']['default'],
        );

        $this->createSimpleProduct(
            category: $tablets,
            brand: $generic,
            name: 'Tableta de Dibujo para Tabletas',
            slug: 'tableta-dibujo-tabletas',
            short: 'Tableta digitalizadora ligera compatible con Windows y Android.',
            description: 'Tableta de dibujo con área activa de 10x6 pulgadas, 8192 niveles de presión y conexión USB-C.',
            salePrice: 189,
            comparePrice: 249,
            cost: 120,
            sku: 'TAB-DIB-01',
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 60, 'shipping' => 8, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['tableta-dibujo-tabletas']['default'],
        );

        $this->createPhone(
            category: $hombre,
            brand: $generic,
            name: 'Polo Manga Corta Básico',
            slug: 'polo-manga-corta-basico',
            salePrices: [49.9, 49.9, 49.9],
            comparePrice: 69.9,
            cost: 25,
            colors: [
                ['name' => 'Negro', 'value' => $black],
                ['name' => 'Rojo', 'value' => $red],
            ],
            sizes: [
                ['name' => 'S', 'value' => $tallaS],
                ['name' => 'M', 'value' => $tallaM],
                ['name' => 'L', 'value' => $tallaL],
            ],
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 80, 'shipping' => 6, 'dispatch' => 2],
            ],
            images: self::REAL_IMAGES['polo-manga-corta-basico'],
        );

        $this->createSimpleProduct(
            category: $cocina,
            brand: $generic,
            name: 'Juego de Ollas Antiadherentes',
            slug: 'juego-ollas-antiadherentes',
            short: 'Juego de 5 ollas con tapa de vidrio.',
            description: 'Juego de 5 ollas antiadherentes con tapa de vidrio templado, aptas para cocinas a gas e inducción.',
            salePrice: 329,
            comparePrice: 399,
            cost: 210,
            sku: 'OLL-5P-ANT',
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 20, 'shipping' => 35, 'dispatch' => 3],
            ],
            images: self::REAL_IMAGES['juego-ollas-antiadherentes']['default'],
        );

        /*
        |--------------------------------------------------------------------------
        | Productos nuevos (todas las categorías)
        |--------------------------------------------------------------------------
        */

        $this->createPhone(
            category: $celulares,
            brand: $apple,
            name: 'Smartphone Apple iPhone 13',
            slug: 'smartphone-apple-iphone-13',
            salePrices: [2199, 2199],
            comparePrice: 2499,
            cost: 1750,
            colors: [
                ['name' => 'Negro', 'value' => $black],
                ['name' => 'Blanco', 'value' => $white],
            ],
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 15, 'shipping' => 12, 'dispatch' => 2],
                ['supplier' => $supplierB, 'stock' => 10, 'shipping' => 15, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['smartphone-apple-iphone-13'],
        );

        $this->createPhone(
            category: $celulares,
            brand: $xiaomi,
            name: 'Xiaomi POCO X5 Pro 5G',
            slug: 'xiaomi-poco-x5-pro',
            salePrices: [1399],
            comparePrice: 1599,
            cost: 1050,
            colors: [
                ['name' => 'Azul', 'value' => $blue],
            ],
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 35, 'shipping' => 10, 'dispatch' => 3],
            ],
            images: self::REAL_IMAGES['xiaomi-poco-x5-pro'],
        );

        $this->createSimpleProduct(
            category: $laptops,
            brand: $hp,
            name: 'Laptop HP Pavilion 15',
            slug: 'laptop-hp-pavilion-15',
            short: 'Laptop con procesador Intel Core i5 de 13.ª generación y 16GB RAM.',
            description: 'Laptop HP Pavilion 15 con pantalla Full HD de 15.6", procesador Intel Core i5 de 13.ª generación, 16GB RAM, SSD de 512GB y Windows 11.',
            salePrice: 2999,
            comparePrice: 3499,
            cost: 2300,
            sku: 'HP-PAV15-I5-16',
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 10, 'shipping' => 25, 'dispatch' => 3],
                ['supplier' => $supplierB, 'stock' => 6, 'shipping' => 30, 'dispatch' => 5],
            ],
            images: self::REAL_IMAGES['laptop-hp-pavilion-15']['default'],
        );

        $this->createSimpleProduct(
            category: $tablets,
            brand: $samsung,
            name: 'Tablet Samsung Galaxy Tab A9',
            slug: 'tablet-samsung-galaxy-tab-a9',
            short: 'Tablet de 11 pulgadas con 128GB y batería de larga duración.',
            description: 'Tablet Samsung Galaxy Tab A9 con pantalla TFT de 11", 4GB RAM, 128GB de almacenamiento y batería para todo el día. Ideal para estudios y entretenimiento.',
            salePrice: 899,
            comparePrice: 1049,
            cost: 650,
            sku: 'SAM-TABA9-128',
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 22, 'shipping' => 15, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['tablet-samsung-galaxy-tab-a9']['default'],
        );

        $this->createPhone(
            category: $hombre,
            brand: $generic,
            name: 'Jeans Slim Hombre',
            slug: 'jeans-slim-hombre',
            salePrices: [89.9, 89.9, 89.9, 89.9, 89.9, 89.9],
            comparePrice: 119.9,
            cost: 45,
            colors: [
                ['name' => 'Azul', 'value' => $blue],
                ['name' => 'Negro', 'value' => $black],
            ],
            sizes: [
                ['name' => 'S', 'value' => $tallaS],
                ['name' => 'M', 'value' => $tallaM],
                ['name' => 'L', 'value' => $tallaL],
            ],
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 60, 'shipping' => 6, 'dispatch' => 2],
            ],
            images: self::REAL_IMAGES['jeans-slim-hombre'],
        );

        $this->createPhone(
            category: $hombre,
            brand: $generic,
            name: 'Camisa Manga Larga Hombre',
            slug: 'camisa-manga-larga-hombre',
            salePrices: [79.9, 79.9, 79.9, 79.9, 79.9, 79.9],
            comparePrice: 99.9,
            cost: 38,
            colors: [
                ['name' => 'Blanco', 'value' => $white],
                ['name' => 'Azul', 'value' => $blue],
            ],
            sizes: [
                ['name' => 'S', 'value' => $tallaS],
                ['name' => 'M', 'value' => $tallaM],
                ['name' => 'L', 'value' => $tallaL],
            ],
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 70, 'shipping' => 6, 'dispatch' => 2],
            ],
            images: self::REAL_IMAGES['camisa-manga-larga-hombre'],
        );

        $this->createPhone(
            category: $mujer,
            brand: $generic,
            name: 'Vestido Estampado de Verano',
            slug: 'vestido-estampado-verano',
            salePrices: [109.9, 109.9, 109.9, 109.9, 109.9, 109.9, 109.9, 109.9, 109.9],
            comparePrice: 149.9,
            cost: 55,
            colors: [
                ['name' => 'Azul', 'value' => $blue],
                ['name' => 'Blanco', 'value' => $white],
                ['name' => 'Rojo', 'value' => $red],
            ],
            sizes: [
                ['name' => 'S', 'value' => $tallaS],
                ['name' => 'M', 'value' => $tallaM],
                ['name' => 'L', 'value' => $tallaL],
            ],
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 40, 'shipping' => 8, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['vestido-estampado-verano'],
        );

        $this->createPhone(
            category: $mujer,
            brand: $generic,
            name: 'Blazer Femenino Clásico',
            slug: 'blazer-femenino-clasico',
            salePrices: [159.9, 159.9, 159.9, 159.9, 159.9, 159.9],
            comparePrice: 199.9,
            cost: 85,
            colors: [
                ['name' => 'Negro', 'value' => $black],
                ['name' => 'Beige', 'value' => $beige],
            ],
            sizes: [
                ['name' => 'S', 'value' => $tallaS],
                ['name' => 'M', 'value' => $tallaM],
                ['name' => 'L', 'value' => $tallaL],
            ],
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 30, 'shipping' => 8, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['blazer-femenino-clasico'],
        );

        $this->createSimpleProduct(
            category: $mujer,
            brand: $generic,
            name: 'Bolso de Mano de Cuero',
            slug: 'bolso-cuero-mujer',
            short: 'Bolso elegante de cuero sintético con correa ajustable.',
            description: 'Bolso de mano en cuero sintético de alta calidad, con compartimentos interiores, cierre metálico y correa ajustable. Perfecto para el día a día.',
            salePrice: 129.9,
            comparePrice: 169.9,
            cost: 68,
            sku: 'BOL-COB-001',
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 25, 'shipping' => 8, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['bolso-cuero-mujer']['default'],
        );

        $this->createSimpleProduct(
            category: $cocina,
            brand: $generic,
            name: 'Set de Cuchillos de Chef en Acero',
            slug: 'set-cuchillos-chef-acero',
            short: 'Set de 7 cuchillos con bloque de madera.',
            description: 'Set de 7 cuchillos de chef en acero inoxidable VG-10 con mango ergonómico, tijeras y bloque de almacenamiento de madera.',
            salePrice: 249,
            comparePrice: 319,
            cost: 160,
            sku: 'CUC-7P-ACR',
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 18, 'shipping' => 20, 'dispatch' => 3],
            ],
            images: self::REAL_IMAGES['set-cuchillos-chef-acero']['default'],
        );

        $this->createSimpleProduct(
            category: $cocina,
            brand: $generic,
            name: 'Batidora de Mano 5 Velocidades',
            slug: 'batidora-mano-5-velocidades',
            short: 'Batidora de mano con 5 velocidades y accesorios.',
            description: 'Batidora de mano con 5 velocidades, función turbo, 700W y accesorios para batir y picar. Ideal para cremas, salsas y batidos.',
            salePrice: 139,
            comparePrice: 179,
            cost: 80,
            sku: 'BAT-5V-700',
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 28, 'shipping' => 12, 'dispatch' => 3],
            ],
            images: self::REAL_IMAGES['batidora-mano-5-velocidades']['default'],
        );

        $this->createSimpleProduct(
            category: $dormitorio,
            brand: $generic,
            name: 'Juego de Sábanas de Algodón (4 piezas)',
            slug: 'juego-sabanas-algodon-4p',
            short: 'Juego de sábanas 100% algodón, color azul suave.',
            description: 'Juego de sábanas de 4 piezas en algodón peruano suave: sábana inferior, sábana superior, 2 fundas de almohada. Disponible en tamaño full.',
            salePrice: 119.9,
            comparePrice: 149.9,
            cost: 70,
            sku: 'SAB-4P-ALG',
            suppliers: [
                ['supplier' => $supplierA, 'stock' => 32, 'shipping' => 15, 'dispatch' => 3],
            ],
            images: self::REAL_IMAGES['juego-sabanas-algodon-4p']['default'],
        );

        $this->createSimpleProduct(
            category: $dormitorio,
            brand: $generic,
            name: 'Acolchado Queen de Algodón',
            slug: 'acolchado-queen-algodon',
            short: 'Acolchado suave con diseño premium para cama queen.',
            description: 'Acolchado acolchado con relleno hipoalergénico, perfecto para cama queen size. Fácil de lavar a máquina y de tacto sedoso.',
            salePrice: 179.9,
            comparePrice: 229.9,
            cost: 110,
            sku: 'ACO-QN-ALG',
            suppliers: [
                ['supplier' => $supplierB, 'stock' => 20, 'shipping' => 20, 'dispatch' => 4],
            ],
            images: self::REAL_IMAGES['acolchado-queen-algodon']['default'],
        );
    }

    private function createAttributeValue(Attribute $attribute, string $value, ?string $color, int $sort): AttributeValue
    {
        return AttributeValue::firstOrCreate(
            ['attribute_id' => $attribute->id, 'slug' => Str::slug($value)],
            ['value' => $value, 'color' => $color, 'sort_order' => $sort, 'is_active' => true],
        );
    }

    private function createPhone(
        Category $category,
        Brand $brand,
        string $name,
        string $slug,
        array $salePrices,
        ?float $comparePrice,
        float $cost,
        array $colors,
        array $suppliers,
        array $sizes = [],
        array $images = [],
    ): void {
        if (Product::where('slug', $slug)->exists()) {
            $this->command->warn("Producto ya existe, omitido: {$slug}");

            return;
        }

        $product = $this->createProduct($category, $brand, $name, $slug);

        $priceIndex = 0;

        foreach ($colors as $index => $colorOption) {
            $variant = $this->createVariant(
                $product,
                sku: strtoupper($slug).'-'.Str::slug($colorOption['name']),
                salePrice: $salePrices[$priceIndex++] ?? end($salePrices),
                comparePrice: $comparePrice,
                cost: $cost,
                isDefault: $index === 0,
            );

            ProductVariantAttributeValue::create([
                'product_variant_id' => $variant->id,
                'attribute_id' => $colorOption['value']->attribute_id,
                'attribute_value_id' => $colorOption['value']->id,
            ]);

            $this->createSupplierVariants($variant, $suppliers);
            $this->createImages(
                $product->name,
                $colorOption['name'],
                $variant,
                $index === 0,
                $images[$colorOption['name']] ?? self::imageSetFor($slug, $colorOption['name']),
            );
        }

        foreach ($sizes as $sizeOption) {
            foreach ($colors as $index => $colorOption) {
                $variant = $this->createVariant(
                    $product,
                    sku: strtoupper($slug).'-'.Str::slug($colorOption['name']).'-'.$sizeOption['name'],
                    salePrice: $salePrices[$priceIndex++] ?? end($salePrices),
                    comparePrice: $comparePrice,
                    cost: $cost,
                    isDefault: false,
                );

                ProductVariantAttributeValue::insert([
                    ['product_variant_id' => $variant->id, 'attribute_id' => $colorOption['value']->attribute_id, 'attribute_value_id' => $colorOption['value']->id, 'created_at' => now(), 'updated_at' => now()],
                    ['product_variant_id' => $variant->id, 'attribute_id' => $sizeOption['value']->attribute_id, 'attribute_value_id' => $sizeOption['value']->id, 'created_at' => now(), 'updated_at' => now()],
                ]);

                $this->createSupplierVariants($variant, $suppliers);
                $this->createImages(
                    $product->name,
                    $colorOption['name'],
                    $variant,
                    $index === 0,
                    $images[$colorOption['name']] ?? self::imageSetFor($slug, $colorOption['name']),
                );
            }
        }
    }

    private function createSimpleProduct(
        Category $category,
        Brand $brand,
        string $name,
        string $slug,
        string $short,
        string $description,
        float $salePrice,
        float $comparePrice,
        float $cost,
        string $sku,
        array $suppliers,
        array $images = [],
    ): void {
        if (Product::where('slug', $slug)->exists()) {
            $this->command->warn("Producto ya existe, omitido: {$slug}");

            return;
        }

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => $slug,
            'short_description' => $short,
            'description' => $description,
            'status' => true,
            'is_featured' => true,
            'is_visible' => true,
            'seo_title' => $name,
            'seo_description' => $short,
        ]);

        $variant = $this->createVariant(
            $product,
            sku: $sku,
            salePrice: $salePrice,
            comparePrice: $comparePrice,
            cost: $cost,
            isDefault: true,
        );

        $this->createSupplierVariants($variant, $suppliers);
        $this->createImages($product->name, 'Principal', $variant, true, $images);
    }

    private function createProduct(Category $category, Brand $brand, string $name, string $slug): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => $slug,
            'short_description' => 'Descripción corta de '.$name.'.',
            'description' => 'Descripción completa del producto '.$name.'. Incluye garantía del fabricante y envío rápido desde el proveedor.',
            'status' => true,
            'is_featured' => true,
            'is_visible' => true,
            'seo_title' => $name,
            'seo_description' => 'Descripción corta de '.$name.'.',
        ]);
    }

    private function createVariant(
        Product $product,
        string $sku,
        float $salePrice,
        ?float $comparePrice,
        float $cost,
        bool $isDefault,
    ): ProductVariant {
        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'sale_price' => $salePrice,
            'compare_price' => $comparePrice,
            'cost_price' => $cost,
            'weight' => 0.5,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);
    }

    private function createSupplierVariants(ProductVariant $variant, array $suppliers): void
    {
        foreach ($suppliers as $index => $data) {
            SupplierVariant::create([
                'supplier_id' => $data['supplier']->id,
                'product_variant_id' => $variant->id,
                'supplier_sku' => 'SV-'.$variant->sku.'-'.($index + 1),
                'cost_price' => $variant->cost_price,
                'shipping_cost' => $data['shipping'],
                'stock' => $data['stock'],
                'reserved_stock' => 0,
                'minimum_stock' => 2,
                'estimated_dispatch_days' => $data['dispatch'],
                'is_default' => $index === 0,
                'is_active' => true,
            ]);
        }
    }

    private function createImages(
        string $productName,
        string $label,
        ProductVariant $variant,
        bool $primary,
        array $urls = [],
    ): void {
        $urls = $urls ?: ['https://placehold.co/600x600/EEE/999?text='.rawurlencode($productName.' - '.$label)];

        foreach ($urls as $index => $url) {
            $suffix = $index === 0 ? '' : '-'.($index + 1);
            $slug = strtolower(Str::slug($productName.'-'.$label.$suffix));

            ProductImage::create([
                'product_variant_id' => $variant->id,
                'public_id' => $slug,
                'file_name' => $slug.'.jpg',
                'url' => $url,
                'secure_url' => $url,
                'format' => 'jpeg',
                'size' => 0,
                'width' => 800,
                'height' => 800,
                'is_primary' => $primary && $index === 0,
                'sort_order' => $index,
                'is_active' => true,
                'alt' => $productName.' - '.$label,
            ]);
        }
    }
}
