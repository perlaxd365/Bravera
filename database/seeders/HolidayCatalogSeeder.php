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

/**
 * Catálogo de temporada navideña para la tienda publicada.
 *
 * Cubre el requisito de catálogo mínimo (5+ productos con foto, descripción
 * y precio visible) reutilizando el inventario propio de Brevare
 * (proveedor interno con `code = BREVARE`).
 *
 * Es idempotente: si un producto con el mismo `slug` ya existe, se omite para
 * no pisar cambios hechos desde el panel. Ejecutar con:
 *
 *     php artisan db:seed --class=HolidayCatalogSeeder
 */
class HolidayCatalogSeeder extends Seeder
{
    /**
     * Valores de color usados por las variantes (nombre => hexadecimal).
     *
     * @var array<string, string>
     */
    private const COLORS = [
        'Rojo' => '#dc2626',
        'Verde' => '#2f6b4f',
        'Blanco' => '#f5f5f5',
        'Dorado' => '#d4af37',
        'Multicolor' => '#b45309',
    ];

    /**
     * Categorías que el catálogo necesita (ruta => [nombre, descripción]).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const CATEGORIES = [
        'navidad' => ['Navidad', 'Decoración, juguetes y regalos de temporada navideña.'],
        'navidad/decoracion' => ['Decoración', 'Adornos, coronas, guirnaldas y decoración navideña.'],
        'navidad/animatronicos' => ['Animatrónicos', 'Figuras navideñas con movimiento, luz y sonido.'],
        'hogar/decoracion' => ['Decoración', 'Cuadros, espejos y objetos de decoración.'],
        'hogar/cocina' => ['Cocina', 'Vajilla y utensilios para cocinar y servir.'],
        'tecnologia/iluminacion' => ['Iluminación', 'Luces LED y lámparas decorativas.'],
    ];

    /**
     * Marcas del catálogo (slug => nombre).
     *
     * @var array<string, string>
     */
    private const BRANDS = [
        'mika' => 'MIKA',
        'casa-joven' => 'CASA JOVEN',
    ];

    /**
     * Catálogo de temporada.
     *
     * `regular` es el precio de lista, `discount` el porcentaje de descuento y
     * el precio final (`sale_price`) se calcula a partir de ambos.
     *
     * @var array<string, array<string, mixed>>
     */
    private const CATALOG = [
        'corona-navidad-luces-led-40-cm' => [
            'category' => 'navidad/decoracion',
            'brand' => 'mika',
            'name' => 'Corona Navideña con Luces LED 40 cm',
            'color' => 'Verde',
            'featured' => true,
            'short' => 'Corona de pino artificial 40 cm con luces LED y adornos rojos y dorados.',
            'description' => 'Corona navideña de pino artificial de 40 cm de diámetro, con ramas densas de dos tonalidades que mantienen la forma temporada tras temporada. Incluye luces LED de bajo consumo y un juego de adornos rojos y dorados, además de piñas y bayas decorativas. El aro interior es metálico y liviano, con sujeción lista para colgar en puerta, ventana o pared.',
            'regular' => 129.00,
            'discount' => 30,
            'cost' => 62.00,
            'stock' => 40,
            'weight' => 0.80,
            'images' => [
                'https://images.pexels.com/photos/6063453/pexels-photo-6063453.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/10250347/pexels-photo-10250347.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'arbol-navidad-1-50-m-luces-led' => [
            'category' => 'navidad/decoracion',
            'brand' => 'mika',
            'name' => 'Árbol de Navidad 1.50 m con Luces LED',
            'color' => 'Verde',
            'featured' => true,
            'short' => 'Árbol armable de 1.50 m con luces LED incluidas y base metálica.',
            'description' => 'Árbol de Navidad de 1.50 m de altura con 780 puntas de follaje verde y estructura armable en tres cuerpos, para guardar y volver a usar cada año. Viene con luces LED cálidas ya instaladas, moldeables en las ramas, y base metálica de cuatro patas con refuerzo. Sus ramas son flexibles, así que aceptan adornos de hasta 500 g sin perder forma.',
            'regular' => 329.00,
            'discount' => 25,
            'cost' => 168.00,
            'stock' => 20,
            'weight' => 6.50,
            'images' => [
                'https://images.pexels.com/photos/724375/pexels-photo-724375.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/14578367/pexels-photo-14578367.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'guirnalda-luminosa-led-10-m' => [
            'category' => 'tecnologia/iluminacion',
            'brand' => 'casa-joven',
            'name' => 'Guirnalda Luminosa LED 10 m',
            'color' => 'Multicolor',
            'featured' => true,
            'short' => 'Guirnalda de 10 m con 100 luces LED y 8 modos de encendido.',
            'description' => 'Guirnalda de 10 metros con 100 luces LED de bajo consumo y cable flexible resistente a la humedad, para usar en interiores y zonas cubiertas. Incluye control con 8 modos de encendido (fijo, intermitente, secuencial y combinaciones) y función de memoria. Se conecta a 220 V con enchufe plano y permite empalmar hasta tres guirnaldas del mismo modelo.',
            'regular' => 79.90,
            'discount' => 20,
            'cost' => 34.00,
            'stock' => 60,
            'weight' => 0.40,
            'images' => [
                'https://images.pexels.com/photos/966861/pexels-photo-966861.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/7357310/pexels-photo-7357310.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'set-bolas-navidenas-24-piezas' => [
            'category' => 'navidad/decoracion',
            'brand' => 'mika',
            'name' => 'Set de Bolas Navideñas 24 Piezas',
            'color' => 'Dorado',
            'featured' => true,
            'short' => '24 bolas decorativas en tres tamaños, con acabado mate, brillante y escarchado.',
            'description' => 'Set de 24 bolas navideñas en rojo y dorado, distribuidas en tres tamaños (4, 6 y 8 cm) con acabados mate, brillante y escarchado para dar volumen al árbol. Cada esfera tiene gancho metálico ya montado y colgante reforzado, así que se pueden colgar de inmediato. Al ser plástico resistente no se quiebran al caer y se guardan en la caja incluida.',
            'regular' => 99.90,
            'discount' => 25,
            'cost' => 42.00,
            'stock' => 50,
            'weight' => 0.90,
            'images' => [
                'https://images.pexels.com/photos/29463054/pexels-photo-29463054.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/35279892/pexels-photo-35279892.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'figura-papa-noel-animatronico-45-cm' => [
            'category' => 'navidad/animatronicos',
            'brand' => 'mika',
            'name' => 'Figura de Papá Noel Animatrónico 45 cm',
            'color' => 'Rojo',
            'featured' => true,
            'short' => 'Papá Noel de 45 cm con movimiento, luz y villancicos activados por sensor.',
            'description' => 'Figura de Papá Noel de 45 cm de alto, vestida con saco de tela y detalles bordados, que se mueve y canta villancicos al detectar movimiento. Tiene sensor de proximidad con alcance aproximado de 1.5 m, luz interior y control de volumen, además de apagado automático. Funciona con cuatro pilas AA (no incluidas) y pesa menos de 2 kg, así que se ubica en mesas, recibidores o vitrinas.',
            'regular' => 299.00,
            'discount' => 20,
            'cost' => 148.00,
            'stock' => 15,
            'weight' => 1.80,
            'images' => [
                'https://images.pexels.com/photos/35214349/pexels-photo-35214349.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/29975615/pexels-photo-29975615.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'gnomo-navideno-decorativo-35-cm' => [
            'category' => 'navidad/decoracion',
            'brand' => 'mika',
            'name' => 'Gnomo Navideño Decorativo 35 cm',
            'color' => 'Rojo',
            'featured' => false,
            'short' => 'Gnomo de 35 cm con gorro y barba de felpa, ideal para repisa o recibidor.',
            'description' => 'Gnomo navideño de 35 cm con gorro rojo de fieltro, barba de felpa suave y botas rellenas que le permiten mantenerse de pie sin soporte. El cuerpo va relleno con fibra siliconada y el gorro lleva un alambre interno para darle caída. Es un adorno de interior, fácil de limpiar con paño húmedo.',
            'regular' => 69.90,
            'discount' => 20,
            'cost' => 29.00,
            'stock' => 45,
            'weight' => 0.50,
            'images' => [
                'https://images.pexels.com/photos/29664151/pexels-photo-29664151.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/35195576/pexels-photo-35195576.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'taza-navidad-ceramica-350-ml' => [
            'category' => 'hogar/cocina',
            'brand' => 'casa-joven',
            'name' => 'Taza Navideña de Cerámica 350 ml',
            'color' => 'Rojo',
            'featured' => false,
            'short' => 'Taza de cerámica de 350 ml con diseño navideño y tapa de silicona.',
            'description' => 'Taza de cerámica de 350 ml con diseño navideño en relieve y acabado esmaltado apto para bebidas calientes y frías. Incluye tapa de silicona que conserva la temperatura y evita derrames, además de asa amplia para un agarre cómodo. Es apta para microondas y lavavajillas; se recomienda lavado a mano para conservar el color.',
            'regular' => 49.90,
            'discount' => 20,
            'cost' => 21.00,
            'stock' => 80,
            'weight' => 0.40,
            'images' => [
                'https://images.pexels.com/photos/6606589/pexels-photo-6606589.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/30280120/pexels-photo-30280120.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'calcetines-navidenos-set-4-unidades' => [
            'category' => 'navidad/decoracion',
            'brand' => 'casa-joven',
            'name' => 'Calcetines Navideños Decorativos (Set 4)',
            'color' => 'Rojo',
            'featured' => false,
            'short' => 'Cuatro calcetines de tela con aplicaciones bordadas para chimenea o pared.',
            'description' => 'Set de cuatro calcetines navideños de tela en distintos diseños, con aplicaciones bordadas y presilla reforzada para colgar. Sirven como adorno de chimenea, pared o puerta, y también para guardar regalos pequeños. El forro interior evita que el relleno se marque y el borde superior mantiene la forma sin doblarse.',
            'regular' => 59.90,
            'discount' => 25,
            'cost' => 25.00,
            'stock' => 60,
            'weight' => 0.30,
            'images' => [
                'https://images.pexels.com/photos/6405625/pexels-photo-6405625.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/10530156/pexels-photo-10530156.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'calendario-adviento-24-dias' => [
            'category' => 'navidad/decoracion',
            'brand' => 'casa-joven',
            'name' => 'Calendario de Adviento 24 Días',
            'color' => 'Blanco',
            'featured' => false,
            'short' => 'Calendario de adviento de 24 bolsas de tela para colgar y llenar en casa.',
            'description' => 'Calendario de adviento de 24 bolsitas de tela kraft con numeración impresa y cordón para colgar, pensado para armar la cuenta regresiva a la Navidad en familia. Cada bolsa mide 12 x 18 cm aproximadamente y admite dulces, tarjetas o regalos pequeños. Se instala con una cuerda incluida sobre pared, mueble o chimenea y se guarda doblado.',
            'regular' => 99.90,
            'discount' => 20,
            'cost' => 44.00,
            'stock' => 30,
            'weight' => 0.60,
            'images' => [
                'https://images.pexels.com/photos/19312033/pexels-photo-19312033.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/1871028/pexels-photo-1871028.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
        'camino-de-mesa-navideno-1-80-m' => [
            'category' => 'hogar/decoracion',
            'brand' => 'casa-joven',
            'name' => 'Camino de Mesa Navideño 1.80 m',
            'color' => 'Verde',
            'featured' => false,
            'short' => 'Camino de mesa de 1.80 m con acabado textil y detalles navideños.',
            'description' => 'Camino de mesa de 1.80 m de largo por 35 cm de ancho, con tejido textil de tacto suave y acabado que no se desliza sobre la mesa. Se usa solo o combinado con centros de mesa y candelabros para vestir el comedor en las cenas de diciembre. Se lava a mano con agua fría y se seca a la sombra; el borde va rematado para evitar deshilachados.',
            'regular' => 79.90,
            'discount' => 25,
            'cost' => 33.00,
            'stock' => 35,
            'weight' => 0.50,
            'images' => [
                'https://images.pexels.com/photos/35245972/pexels-photo-35245972.jpeg?auto=compress&cs=tinysrgb&w=900',
                'https://images.pexels.com/photos/9430354/pexels-photo-9430354.jpeg?auto=compress&cs=tinysrgb&w=900',
            ],
        ],
    ];

    public function run(): void
    {
        $supplier = Supplier::query()->where('code', 'BREVARE')->first();

        if (! $supplier) {
            $this->write('No existe el proveedor interno con code=BREVARE. Ejecuta las migraciones antes de sembrar el catálogo.', 'error');

            return;
        }

        DB::transaction(function () use ($supplier) {
            $this->seedCategories();
            $this->seedBrands();
            $colors = $this->seedColors();

            $created = 0;
            $skipped = 0;

            foreach (self::CATALOG as $slug => $data) {
                if (Product::withTrashed()->where('slug', $slug)->exists()) {
                    $skipped++;
                    $this->write("• Omitido (ya existe): {$data['name']}");

                    continue;
                }

                $this->createProduct($slug, $data, $supplier, $colors);
                $created++;
                $this->write("✓ Creado: {$data['name']}");
            }

            $this->write("Catálogo de temporada: {$created} productos creados, {$skipped} omitidos.", 'info');
        });
    }

    /**
     * Escribe una línea en la consola cuando el seeder se ejecuta por CLI.
     */
    private function write(string $message, string $style = 'line'): void
    {
        if (! $this->command) {
            return;
        }

        match ($style) {
            'info' => $this->command->info($message),
            'error' => $this->command->error($message),
            default => $this->command->line($message),
        };
    }

    private function seedCategories(): void
    {
        foreach (self::CATEGORIES as $path => [$name, $description]) {
            $slug = Str::afterLast($path, '/');
            $parentPath = Str::contains($path, '/') ? Str::beforeLast($path, '/') : null;

            $parent = $parentPath
                ? Category::withTrashed()->where('path', $parentPath)->first()
                : null;

            $category = Category::withTrashed()->firstOrNew(['path' => $path]);
            $category->fill([
                'parent_id' => $parent?->id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'position' => $category->position ?: 1,
                'is_visible' => true,
                'is_featured' => $category->is_featured ?? false,
                'meta_title' => $name.' | Brevare',
                'meta_description' => $description,
            ]);

            if ($category->trashed()) {
                $category->restore();
            }

            $category->save();
        }
    }

    private function seedBrands(): void
    {
        foreach (self::BRANDS as $slug => $name) {
            Brand::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true, 'deleted_at' => null],
            );
        }
    }

    /**
     * Devuelve los valores del atributo Color indexados por nombre.
     *
     * @return array<string, AttributeValue>
     */
    private function seedColors(): array
    {
        $attribute = Attribute::withTrashed()->firstOrNew(['slug' => 'color']);
        $attribute->fill([
            'name' => 'Color',
            'type' => 'color',
            'is_filter' => true,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => $attribute->sort_order ?: 1,
        ]);

        if ($attribute->trashed()) {
            $attribute->restore();
        }

        $attribute->save();

        $values = [];

        foreach (self::COLORS as $name => $hex) {
            $value = AttributeValue::withTrashed()->firstOrNew([
                'attribute_id' => $attribute->id,
                'slug' => Str::slug($name),
            ]);

            $value->fill([
                'value' => $name,
                'color' => $hex,
                'sort_order' => $value->sort_order ?: 1,
                'is_active' => true,
            ]);

            if ($value->trashed()) {
                $value->restore();
            }

            $value->save();

            $values[$name] = $value;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, AttributeValue>  $colors
     */
    private function createProduct(string $slug, array $data, Supplier $supplier, array $colors): void
    {
        $category = Category::where('path', $data['category'])->firstOrFail();
        $brand = Brand::where('slug', $data['brand'])->first();

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => $data['name'],
            'slug' => $slug,
            'short_description' => $data['short'],
            'description' => $data['description'],
            'status' => true,
            'is_featured' => (bool) $data['featured'],
            'is_visible' => true,
            'seo_title' => $data['name'].' | Brevare',
            'seo_description' => Str::limit($data['short'], 160, ''),
        ]);

        $discount = (float) $data['discount'];
        $comparePrice = (float) $data['regular'];
        $salePrice = round($comparePrice * (1 - ($discount / 100)), 2);
        $sku = strtoupper(Str::slug($slug, '-'));

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'cost_price' => $data['cost'],
            'sale_price' => $salePrice,
            'compare_price' => $comparePrice,
            'discount_percent' => $discount,
            'weight' => $data['weight'],
            'is_default' => true,
            'is_active' => true,
        ]);

        $color = $colors[$data['color']] ?? null;

        if ($color) {
            ProductVariantAttributeValue::create([
                'product_variant_id' => $variant->id,
                'attribute_id' => $color->attribute_id,
                'attribute_value_id' => $color->id,
            ]);
        }

        SupplierVariant::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'supplier_sku' => 'SV-'.$sku,
            'cost_price' => $data['cost'],
            'shipping_cost' => 9.90,
            'stock' => $data['stock'],
            'reserved_stock' => 0,
            'minimum_stock' => 2,
            'estimated_dispatch_days' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach (array_values($data['images']) as $index => $url) {
            ProductImage::create([
                'product_variant_id' => $variant->id,
                'public_id' => $slug.($index > 0 ? '-'.($index + 1) : ''),
                'file_name' => $slug.($index > 0 ? '-'.($index + 1) : '').'.jpg',
                'url' => $url,
                'secure_url' => $url,
                'format' => 'jpeg',
                'size' => 0,
                'width' => 900,
                'height' => 900,
                'is_primary' => $index === 0,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
