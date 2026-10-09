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
 * Catálogo de demostración: 40 productos con imágenes reales de Pexels.
 *
 * Cada producto declara sus grupos de atributos y el seeder genera el
 * producto cartesiano completo de variantes. Nunca se crean variantes
 * parciales (por ejemplo "solo color" en un producto que además tiene tallas),
 * porque el storefront exige completar todos los grupos antes de comprar.
 */
class ProductSeeder extends Seeder
{
    /**
     * Colores disponibles con su valor hexadecimal.
     *
     * @var array<string, string>
     */
    private const COLORS = [
        'Negro' => '#111111',
        'Blanco' => '#ffffff',
        'Azul marino' => '#1f2a44',
        'Azul' => '#2563eb',
        'Beige' => '#d6c7a1',
        'Gris' => '#9ca3af',
        'Verde' => '#2f6b4f',
        'Marrón' => '#6b4423',
        'Rojo' => '#dc2626',
        'Rosa' => '#e891b0',
    ];

    /**
     * Valores por tipo de atributo (el orden define el `sort_order`).
     *
     * @var array<string, array<int, string>>
     */
    private const ATTRIBUTE_VALUES = [
        'talla' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'talla-calzado' => ['37', '38', '39', '40', '41', '42', '43', '44'],
        'medida' => [
            '28', '30', '32', '34', '36', '38',
            'Queen', 'King',
            '50x70 cm', '70x90 cm',
            '90 cm', '100 cm', '110 cm',
        ],
    ];

    /**
     * Catálogo de demostración.
     *
     * @var array<string, array<string, mixed>>
     */
    private const CATALOG = [
        /*
        |--------------------------------------------------------------------------
        | Moda Hombre
        |--------------------------------------------------------------------------
        */
        'polo-pima-manga-corta' => [
            'category' => 'moda/hombre',
            'brand' => 'kurama',
            'name' => 'Polo Pima Manga Corta',
            'short' => 'Polo de algodón pima con cuello reforzado y ajuste regular.',
            'description' => 'Polo de algodón pima peruano, tejido de fibra larga que mantiene la forma y la suavidad después de varios lavados. Cuello y puños reforzados, costuras triples y etiqueta bordada. Ideal para el uso diario y el trabajo de oficina.',
            'sku' => 'POL-PIMA',
            'price' => 79.90,
            'compare' => 109.00,
            'cost' => 42.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Blanco', 'Azul marino'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/30975998/pexels-photo-30975998.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/28494846/pexels-photo-28494846.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Blanco' => [
                    'https://images.pexels.com/photos/38346474/pexels-photo-38346474.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/36701796/pexels-photo-36701796.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul marino' => [
                    'https://images.pexels.com/photos/21938719/pexels-photo-21938719.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/38010898/pexels-photo-38010898.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'polo-cuello-polo' => [
            'category' => 'moda/hombre',
            'brand' => 'kurama',
            'name' => 'Polo Cuello Polo Algodón',
            'short' => 'Polo peinado de algodón con abertura lateral y ribete en el cuello.',
            'description' => 'Polo de algodón peinado 100% con abertura de tres botones en el cuello y ribete elástico en costuras. Tela de 180 g/m² que mantiene la forma, con espacio ideal para bordado personalizado.',
            'sku' => 'POL-CPOLO',
            'price' => 69.90,
            'compare' => 89.90,
            'cost' => 36.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Blanco', 'Azul', 'Rojo'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Blanco' => [
                    'https://images.pexels.com/photos/17898625/pexels-photo-17898625.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/22745628/pexels-photo-22745628.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/26588155/pexels-photo-26588155.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/18239096/pexels-photo-18239096.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Rojo' => [
                    'https://images.pexels.com/photos/38434455/pexels-photo-38434455.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/38434462/pexels-photo-38434462.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'camisa-slim-fit-lino' => [
            'category' => 'moda/hombre',
            'brand' => 'nordis',
            'name' => 'Camisa Slim Fit Lino',
            'short' => 'Camisa de mezcla lino y algodón con corte slim y cuello italiano.',
            'description' => 'Camisa de mezcla 55% lino y 45% algodón con corte slim, cuello italiano y puño ajustable. El lino regula la temperatura y la mezcla de algodón evita las arrugas excesivas, por lo que funciona bien en días calurosos y en oficina.',
            'sku' => 'CAM-LINO',
            'price' => 119.90,
            'compare' => 159.00,
            'cost' => 68.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Blanco', 'Beige', 'Azul'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Blanco' => [
                    'https://images.pexels.com/photos/17492498/pexels-photo-17492498.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/18398568/pexels-photo-18398568.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/18399171/pexels-photo-18399171.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/18399172/pexels-photo-18399172.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/38285774/pexels-photo-38285774.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/18153493/pexels-photo-18153493.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'jeans-slim-hombre' => [
            'category' => 'moda/hombre',
            'brand' => 'nordis',
            'name' => 'Jeans Slim Hombre',
            'short' => 'Jeans de mezclilla elástica con lavado oscuro y corte slim.',
            'description' => 'Jeans de mezclilla 98% algodón y 2% elastano con lavado oscuro uniforme, cinco bolsillos y remaches reforzados. El corte slim mantiene una silueta limpia sin apretar y el elastano permite moverse con comodidad durante largas jornadas.',
            'sku' => 'JNS-SLIM',
            'price' => 149.90,
            'compare' => 199.00,
            'cost' => 85.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Azul', 'Negro'],
                'medida' => ['30', '32', '34', '36', '38'],
            ],
            'images' => [
                'Azul' => [
                    'https://images.pexels.com/photos/30209161/pexels-photo-30209161.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/15221683/pexels-photo-15221683.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Negro' => [
                    'https://images.pexels.com/photos/38110456/pexels-photo-38110456.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/17794993/pexels-photo-17794993.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'chino-cotton-twill' => [
            'category' => 'moda/hombre',
            'brand' => 'nordis',
            'name' => 'Chino Cotton Twill',
            'short' => 'Pantalón chino de algodón twill con 2% de elastano.',
            'description' => 'Pantalón chino de cotton twill de 240 g/m² con un 2% de elastano, corte recto y rise medio. Tejido diagonal resistente que mantiene la forma y el color después de múltiples lavados, con forro de bolsillos en algodón para comodidad.',
            'sku' => 'CHO-TWILL',
            'price' => 139.90,
            'compare' => 179.00,
            'cost' => 79.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Beige', 'Azul', 'Negro'],
                'medida' => ['30', '32', '34', '36', '38'],
            ],
            'images' => [
                'Beige' => [
                    'https://images.pexels.com/photos/18817925/pexels-photo-18817925.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/16238583/pexels-photo-16238583.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/19071478/pexels-photo-19071478.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/28387466/pexels-photo-28387466.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Negro' => [
                    'https://images.pexels.com/photos/19164195/pexels-photo-19164195.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/17794993/pexels-photo-17794993.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'short-deportivo-ligero' => [
            'category' => 'moda/hombre',
            'brand' => 'nordis',
            'name' => 'Short Deportivo Ligero',
            'short' => 'Short de running con forro interior y cintura elástica.',
            'description' => 'Short de running de microfibra con dos elásticos en la cintura y forro interior que evita transparencias. Secado rápido y ligero, pensado para entrenar o para el verano.',
            'sku' => 'SHT-DEP',
            'price' => 69.90,
            'compare' => 89.90,
            'cost' => 34.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Gris', 'Azul'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/18178462/pexels-photo-18178462.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/18178103/pexels-photo-18178103.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Gris' => [
                    'https://images.pexels.com/photos/32886504/pexels-photo-32886504.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/17440997/pexels-photo-17440997.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/19473529/pexels-photo-19473529.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/30710156/pexels-photo-30710156.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'sudadera-capucha-algodon' => [
            'category' => 'moda/hombre',
            'brand' => 'kurama',
            'name' => 'Sudadera con Capucha Algodón',
            'short' => 'Sudadera de algodón cepillado con capucha forrada y bolsillo canguro.',
            'description' => 'Sudadera de algodón cepillado de 320 g/m² con capucha doble forrada, cordones planos y bolsillo canguro. Ribetes elastizados en puños y bajo para mantener la forma después de lavar.',
            'sku' => 'SUD-CAPU',
            'price' => 129.90,
            'compare' => 169.00,
            'cost' => 72.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Gris', 'Beige'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/28701960/pexels-photo-28701960.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/30095394/pexels-photo-30095394.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Gris' => [
                    'https://images.pexels.com/photos/26569577/pexels-photo-26569577.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/30257624/pexels-photo-30257624.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/29446722/pexels-photo-29446722.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/30257619/pexels-photo-30257619.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'casaca-impermeable-urbana' => [
            'category' => 'moda/hombre',
            'brand' => 'kurama',
            'name' => 'Casaca Impermeable Urbana',
            'short' => 'Chaqueta urbana con membrana impermeable y capucha desmontable.',
            'description' => 'Chaqueta urbana con membrana impermeable y respirable, costuras selladas y capucha desmontable. Tela exterior repelente al agua con forro térmico ligero, bolsillos con cierre y puños ajustables.',
            'sku' => 'CAS-URB',
            'price' => 249.90,
            'compare' => 329.00,
            'cost' => 138.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Verde', 'Azul'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/37281839/pexels-photo-37281839.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/30002591/pexels-photo-30002591.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Verde' => [
                    'https://images.pexels.com/photos/33352281/pexels-photo-33352281.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/34762431/pexels-photo-34762431.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/19644652/pexels-photo-19644652.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/35583458/pexels-photo-35583458.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Moda Mujer
        |--------------------------------------------------------------------------
        */
        'blusa-satinada' => [
            'category' => 'moda/mujer',
            'brand' => 'alba',
            'name' => 'Blusa Satinada',
            'short' => 'Blusa de satén con brillo sutil, mangas largas y cierre frontal.',
            'description' => 'Blusa de satén con caída fluida y brillo sutil, mangas largas y botonadura frontal. El satén mantiene el cuerpo en su sitio sin apretar y se combina igual de bien con pantalón de vestir como con jean.',
            'sku' => 'BLU-SATIN',
            'price' => 119.90,
            'compare' => 159.00,
            'cost' => 64.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Beige', 'Rojo'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/20364759/pexels-photo-20364759.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/20303783/pexels-photo-20303783.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/15764810/pexels-photo-15764810.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/31952885/pexels-photo-31952885.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Rojo' => [
                    'https://images.pexels.com/photos/39835428/pexels-photo-39835428.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/38492361/pexels-photo-38492361.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'crop-top-deportivo' => [
            'category' => 'moda/mujer',
            'brand' => 'alba',
            'name' => 'Crop Top Deportivo',
            'short' => 'Crop top de algodón elástico con costuras planas.',
            'description' => 'Crop top de algodón elástico con costuras planas, soporte medio y dobladillo ancho. Pensado para entrenar o como pieza de verano.',
            'sku' => 'CRP-TOP',
            'price' => 59.90,
            'compare' => 79.90,
            'cost' => 28.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Blanco', 'Rosa'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/20333641/pexels-photo-20333641.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/37987776/pexels-photo-37987776.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Blanco' => [
                    'https://images.pexels.com/photos/18586984/pexels-photo-18586984.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/10321731/pexels-photo-10321731.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Rosa' => [
                    'https://images.pexels.com/photos/16895093/pexels-photo-16895093.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/16375183/pexels-photo-16375183.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'top-deportivo-seamless' => [
            'category' => 'moda/mujer',
            'brand' => 'alba',
            'name' => 'Top Deportivo Seamless',
            'short' => 'Top de secado rápido con tejido sin costuras y sostén reforzado.',
            'description' => 'Top de licra con tejido sin costuras que reduce rozaduras, sostén reforzado y tirantes anchos. Secado rápido y opacidad media, pensado para el entrenamiento y el día a día.',
            'sku' => 'TOP-SEAML',
            'price' => 89.90,
            'compare' => 119.00,
            'cost' => 44.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Beige', 'Azul'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/20325649/pexels-photo-20325649.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/6311497/pexels-photo-6311497.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/7319722/pexels-photo-7319722.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/5700048/pexels-photo-5700048.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/31245350/pexels-photo-31245350.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/35436765/pexels-photo-35436765.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'vestido-midi-floreado' => [
            'category' => 'moda/mujer',
            'brand' => 'alba',
            'name' => 'Vestido Midi Florido',
            'short' => 'Vestido midi de lawn con estampado floral y mangas abombadas.',
            'description' => 'Vestido midi de lawn con estampado floral, mangas abombadas y cintura elastizada. Tela fresca con forro ligero, cierre posterior y vuelo Amplio hasta debajo de la rodilla.',
            'sku' => 'VST-MIDI',
            'price' => 139.90,
            'compare' => 189.00,
            'cost' => 76.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Azul', 'Rojo', 'Verde'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Azul' => [
                    'https://images.pexels.com/photos/27305391/pexels-photo-27305391.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/29942976/pexels-photo-29942976.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Rojo' => [
                    'https://images.pexels.com/photos/16099136/pexels-photo-16099136.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/23857017/pexels-photo-23857017.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Verde' => [
                    'https://images.pexels.com/photos/17990779/pexels-photo-17990779.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/18628873/pexels-photo-18628873.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'falda-midi-plegada' => [
            'category' => 'moda/mujer',
            'brand' => 'marbell',
            'name' => 'Falda Midi Plrugada',
            'short' => 'Falda midi plisada con cintura alta y forroPrograms.',
            'description' => 'Falda midi con plisado permanente, cintura alta con cremallera lateral y forro interior de shorts. El plisado conserva el volumen después del lavado.',
            'sku' => 'FLD-MIDI',
            'price' => 129.90,
            'compare' => 169.00,
            'cost' => 68.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Beige'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/32174990/pexels-photo-32174990.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/38908702/pexels-photo-38908702.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/18589814/pexels-photo-18589814.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/4663319/pexels-photo-4663319.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'jeans-alto-cintura-mujer' => [
            'category' => 'moda/mujer',
            'brand' => 'marbell',
            'name' => 'Jeans Alto de Cintura',
            'short' => 'Jean de tiro alto con lavado medio y pierna recta.',
            'description' => 'Jean de tiro alto con cintura elasticizada en la parte trasera, lavado medio y pierna recta. Mezclilla con 2% de elastano para comodidad, con cinco bolsillos y remaches en los puntos de tensión.',
            'sku' => 'JNS-ALTO',
            'price' => 139.90,
            'compare' => 189.00,
            'cost' => 78.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Azul', 'Negro', 'Beige'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Azul' => [
                    'https://images.pexels.com/photos/38783346/pexels-photo-38783346.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/39457662/pexels-photo-39457662.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Negro' => [
                    'https://images.pexels.com/photos/8389821/pexels-photo-8389821.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/11111412/pexels-photo-11111412.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/17135748/pexels-photo-17135748.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/39457741/pexels-photo-39457741.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'blazer-femenino-clasico' => [
            'category' => 'moda/mujer',
            'brand' => 'marbell',
            'name' => 'Blazer Femenino Clásico',
            'short' => 'Blazer de sastre con hombros estructurados y forro interior.',
            'description' => 'Blazer de sastre con hombros ligeramente estructurados, cierre de un botón y forro interior en seda artificial. Tela de mezcla de lana con caída firme, ideal para oficina y ocasiones formales.',
            'sku' => 'BLZ-CLAS',
            'price' => 259.90,
            'compare' => 349.00,
            'cost' => 142.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Beige'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/36752320/pexels-photo-36752320.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/36752323/pexels-photo-36752323.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/35983964/pexels-photo-35983964.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/7311207/pexels-photo-7311207.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'cardigan-lana-suave' => [
            'category' => 'moda/mujer',
            'brand' => 'marbell',
            'name' => 'Cardigan de Lana Suave',
            'short' => 'Cardigan de punto grueso con botones y Manga Wide.',
            'description' => 'Cardigan de mezcla de lana con punto grueso y botones de madera. Tejido de 380 g/m² que abriga sin peso excessivo y con mangas anchas relajadas.',
            'sku' => 'CRD-LANA',
            'price' => 189.90,
            'compare' => 259.00,
            'cost' => 98.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Beige', 'Negro', 'Verde'],
                'talla' => ['S', 'M', 'L', 'XL'],
            ],
            'images' => [
                'Beige' => [
                    'https://images.pexels.com/photos/8989555/pexels-photo-8989555.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/10174127/pexels-photo-10174127.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Negro' => [
                    'https://images.pexels.com/photos/7043490/pexels-photo-7043490.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/11423786/pexels-photo-11423786.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Verde' => [
                    'https://images.pexels.com/photos/15054259/pexels-photo-15054259.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/31422189/pexels-photo-31422189.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Calzado
        |--------------------------------------------------------------------------
        */
        'sneakers-urbana-cuero' => [
            'category' => 'calzado/sneakers',
            'brand' => 'stride',
            'name' => 'Sneakers Urbana de Cuero',
            'short' => 'Sneakers de cuero con suela de goma vulcanizada.',
            'description' => 'Sneakers de cuero con suela de goma vulcanizada, plantilla de espuma con memoria y forro acolchado. El cuero mantiene la forma con el uso y la suela es resistente para el uso diario.',
            'sku' => 'SNK-URB',
            'price' => 219.90,
            'compare' => 299.00,
            'cost' => 124.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Blanco', 'Negro'],
                'talla-calzado' => ['38', '39', '40', '41', '42', '43', '44'],
            ],
            'images' => [
                'Blanco' => [
                    'https://images.pexels.com/photos/7193626/pexels-photo-7193626.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/21419626/pexels-photo-21419626.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Negro' => [
                    'https://images.pexels.com/photos/9666619/pexels-photo-9666619.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/847371/pexels-photo-847371.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'running-amortiguado' => [
            'category' => 'calzado/zapatillas',
            'brand' => 'stride',
            'name' => 'Running Amortiguado',
            'short' => 'Zapatillas de running con entreda de espuma y drop de 8 mm.',
            'description' => 'Zapatillas de running con entresuela de espuma de doble densidad, drop de 8 mm y upper de malla transpirable. Peso ligero y diseño óptimo para trotes diarios y entrenamientos.',
            'sku' => 'RUN-AMRT',
            'price' => 239.90,
            'compare' => 319.00,
            'cost' => 138.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Azul'],
                'talla-calzado' => ['38', '39', '40', '41', '42', '43', '44'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/797637/pexels-photo-797637.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/29096394/pexels-photo-29096394.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/1461538/pexels-photo-1461538.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/13236693/pexels-photo-13236693.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'sandalias-tiras-cuero' => [
            'category' => 'calzado/sandalias',
            'brand' => 'terra',
            'name' => 'Sandalias de Tiras de Cuero',
            'short' => 'Sandalias de tiras de cuero con suela de gel comfort.',
            'description' => 'Sandalias de tiras de cuero genuino con hebilla regulable, forro de piel y suela de gel comfort. El cuero toma la forma del pie con el uso para mayor comodidad.',
            'sku' => 'SND-TIRAS',
            'price' => 159.90,
            'compare' => 219.00,
            'cost' => 86.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Beige'],
                'talla-calzado' => ['37', '38', '39', '40', '41'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/27204292/pexels-photo-27204292.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/31129840/pexels-photo-31129840.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/26965817/pexels-photo-26965817.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/27204291/pexels-photo-27204291.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'botas-cuero' => [
            'category' => 'calzado/botas',
            'brand' => 'terra',
            'name' => 'Botas de Cuero',
            'short' => 'Botas de cuero con suela gruesa y forro térmico.',
            'description' => 'Botas de cuero vacuno con suela gruesa de goma, forro térmico y cierre lateral con cremallera. Suela antideslizante y plantilla removible para días fríos.',
            'sku' => 'BOT-CUERO',
            'price' => 329.90,
            'compare' => 429.00,
            'cost' => 188.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Marrón'],
                'talla-calzado' => ['38', '39', '40', '41', '42', '43'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/26732212/pexels-photo-26732212.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/27174564/pexels-photo-27174564.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Marrón' => [
                    'https://images.pexels.com/photos/26851199/pexels-photo-26851199.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/16195409/pexels-photo-16195409.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'zapatillas-running-ligeras' => [
            'category' => 'calzado/zapatillas',
            'brand' => 'stride',
            'name' => 'Zapatillas Running Ligeras',
            'short' => 'Zapatillas ultraligeras de 210 g conupper de sock.',
            'description' => 'Zapatillas de running ultraligeras (aprox. 210 g) con upper tipo calcetín sin costuras y entresuela de espuma TPU. Diseñadas para recorridos largos y días de uso diario.',
            'sku' => 'ZPT-LIGHT',
            'price' => 199.90,
            'compare' => 269.00,
            'cost' => 112.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Blanco', 'Rosa'],
                'talla-calzado' => ['38', '39', '40', '41', '42', '43', '44'],
            ],
            'images' => [
                'Blanco' => [
                    'https://images.pexels.com/photos/29499910/pexels-photo-29499910.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/7880250/pexels-photo-7880250.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Rosa' => [
                    'https://images.pexels.com/photos/3051149/pexels-photo-3051149.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/11827631/pexels-photo-11827631.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'sandalias-deportivas' => [
            'category' => 'calzado/sandalias',
            'brand' => 'terra',
            'name' => 'Sandalias Deportivas',
            'short' => 'Sandalias con entresuela de espuma y plantilla ergonómica.',
            'description' => 'Sandalias deportivas con entresuela de espuma y plantilla ergonómica con arco. Suela de goma ligera y tira ancha que favorece la ventilación.',
            'sku' => 'SND-DEP',
            'price' => 89.90,
            'compare' => 129.00,
            'cost' => 46.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Blanco'],
                'talla-calzado' => ['38', '39', '40', '41'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/18186215/pexels-photo-18186215.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/14313221/pexels-photo-14313221.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Blanco' => [
                    'https://images.pexels.com/photos/18368114/pexels-photo-18368114.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/26954370/pexels-photo-26954370.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'botas-impermeables' => [
            'category' => 'calzado/botas',
            'brand' => 'terra',
            'name' => 'Botas Impermeables',
            'short' => 'Botas de trekking impermeables con membrana y suela de agarre.',
            'description' => 'Botas de trekking con membrana impermeable y transpirable, refuerzo en la puntera y suela de agarre profundo. Ideales para montaña, lluvia y caminos de tierra.',
            'sku' => 'BOT-IMPR',
            'price' => 379.90,
            'compare' => 499.00,
            'cost' => 218.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Verde'],
                'talla-calzado' => ['38', '39', '40', '41', '42', '43'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/37409595/pexels-photo-37409595.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/32669429/pexels-photo-32669429.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Verde' => [
                    'https://images.pexels.com/photos/30229931/pexels-photo-30229931.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/3866538/pexels-photo-3866538.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'mocasines-cuero' => [
            'category' => 'calzado/mocasines',
            'brand' => 'terra',
            'name' => 'Mocasines de Cuero Suave',
            'short' => 'Mocasines de cuero flexible con forro de piel y plantilla acolchada.',
            'description' => 'Mocasines de cuero flexible con forro de piel y plantilla de espuma con memoria. El diseño sin cordones facilita el calce y la puntera redondeada aporta comodidad en jornadas largas.',
            'sku' => 'MCS-CUERO',
            'price' => 249.90,
            'compare' => 329.00,
            'cost' => 142.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Marrón', 'Negro'],
                'talla-calzado' => ['38', '39', '40', '41', '42', '43'],
            ],
            'images' => [
                'Marrón' => [
                    'https://images.pexels.com/photos/18054235/pexels-photo-18054235.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/27141834/pexels-photo-27141834.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Negro' => [
                    'https://images.pexels.com/photos/19556446/pexels-photo-19556446.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/9992899/pexels-photo-9992899.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Hogar
        |--------------------------------------------------------------------------
        */
        'juego-ollas-antiderrapantes' => [
            'category' => 'hogar/cocina',
            'brand' => 'vitacasa',
            'name' => 'Juego de Ollas Antiadherentes 8 Piezas',
            'short' => 'Juego de 8 piezas con antiadherente de tres capas y mangoSafe.',
            'description' => 'Juego de 8 piezas de aluminio con antiadherente de tres capas: cacerola de 24 cm, olla de 28 cm, olla alta de 20 cm y cuatro sartenes. Mangos térmicos que no conducen el calor y tapa de vidrio con válvula de vapor.',
            'sku' => 'OLL-ANTI',
            'price' => 349.90,
            'compare' => 459.00,
            'cost' => 208.00,
            'featured' => true,
            'attributes' => [],
            'images' => [
                'default' => [
                    'https://images.pexels.com/photos/31110049/pexels-photo-31110049.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/16510622/pexels-photo-16510622.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'set-cuchillos-chef-acero' => [
            'category' => 'hogar/cocina',
            'brand' => 'vitacasa',
            'name' => 'Set de Cuchillos Chef Acero',
            'short' => 'Set de 5 cuchillos con hoja de acero inoxidable y mango antideslizante.',
            'description' => 'Set de 5 cuchillos con hoja de acero inoxidable de alta dureza y mango antideslizante: chef de 20 cm, santoku, panera y dos de deshuesar. Bloque de madera incluido para guardar y proteger las hojas.',
            'sku' => 'CCH-CHEF',
            'price' => 229.90,
            'compare' => 309.00,
            'cost' => 132.00,
            'featured' => false,
            'attributes' => [],
            'images' => [
                'default' => [
                    'https://images.pexels.com/photos/12936944/pexels-photo-12936944.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/16457092/pexels-photo-16457092.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'batidora-mano-5-velocidades' => [
            'category' => 'hogar/cocina',
            'brand' => 'vitacasa',
            'name' => 'Batidora de Mano 5 Velocidades',
            'short' => 'Batidora de 5 velocidades con 3 accesorios y bowl de acero.',
            'description' => 'Batidora eléctrica de 5 velocidades y 250 W con accesorios de batido, amasador de masa y varilla para claras. Incluye bowl de acero inoxidable con base antiderrapante, para masas, tortas y salsas.',
            'sku' => 'BAT-MANO',
            'price' => 179.90,
            'compare' => 249.00,
            'cost' => 96.00,
            'featured' => false,
            'attributes' => [],
            'images' => [
                'default' => [
                    'https://images.pexels.com/photos/11881952/pexels-photo-11881952.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/6996189/pexels-photo-6996189.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'juego-sabanas-algodon-4-piezas' => [
            'category' => 'hogar/dormitorio',
            'brand' => 'vitacasa',
            'name' => 'Juego de Sábanas Algodón 4 Piezas',
            'short' => 'Juego de sábanas de algodón percal 200 hilos con encaje elástico.',
            'description' => 'Juego de 4 piezas de algodón percal de 200 hilos: sábana bajera con elástico, sábana encimera y dos fundas de almohada. Tejido cerrado, fresco y resistente a los lavados repetidos sin perder color.',
            'sku' => 'SAB-4P',
            'price' => 189.90,
            'compare' => 259.00,
            'cost' => 98.00,
            'featured' => true,
            'attributes' => [
                'medida' => ['Queen', 'King'],
            ],
            'images' => [
                'Queen' => [
                    'https://images.pexels.com/photos/4153147/pexels-photo-4153147.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/12200060/pexels-photo-12200060.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'King' => [
                    'https://images.pexels.com/photos/4469180/pexels-photo-4469180.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/12700461/pexels-photo-12700461.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'acolchado-queen-algodonado' => [
            'category' => 'hogar/dormitorio',
            'brand' => 'vitacasa',
            'name' => 'Acolchado Queen Algodonado',
            'short' => 'Acolchado de algodón con relleno siliconado y diseño reversible.',
            'description' => 'Acolchado de algodón con relleno siliconado, pespuntes en canales y diseño reversible para cambiar el look en segundos. Suave, transpirable y fácil de lavar a máquina.',
            'sku' => 'ACL-QUEEN',
            'price' => 259.90,
            'compare' => 349.00,
            'cost' => 142.00,
            'featured' => false,
            'attributes' => [
                'medida' => ['Queen', 'King'],
            ],
            'images' => [
                'Queen' => [
                    'https://images.pexels.com/photos/4080048/pexels-photo-4080048.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/21705875/pexels-photo-21705875.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'King' => [
                    'https://images.pexels.com/photos/7591040/pexels-photo-7591040.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/12836535/pexels-photo-12836535.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'set-toallas-algodon-turco' => [
            'category' => 'hogar/dormitorio',
            'brand' => 'vitacasa',
            'name' => 'Set de Toallas Algodón Turco',
            'short' => 'Set de 4 toallas de algodón turco con pelo alto y buena absorción.',
            'description' => 'Set de 4 toallas de algodón turco de 600 g/m² con pelo alto, gran absorción y secado rápido. Incluye dos toallas de baño, una de cara y una de mano.',
            'sku' => 'TOL-TURCO',
            'price' => 119.90,
            'compare' => 169.00,
            'cost' => 62.00,
            'featured' => false,
            'attributes' => [],
            'images' => [
                'default' => [
                    'https://images.pexels.com/photos/30982437/pexels-photo-30982437.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/7691101/pexels-photo-7691101.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'cuadro-decorativo-minimalista' => [
            'category' => 'hogar/decoracion',
            'brand' => 'aurora',
            'name' => 'Cuadro Decorativo Minimalista',
            'short' => 'Cuadro con marco de madera de pino e impresión de arte sobre lienzo.',
            'description' => 'Cuadro con marco de madera de pino de 2 cm, vidrio templado e impresión de arte de alta definición sobre lienzo. Incluye gancho y accesorios para instalarlo en la pared.',
            'sku' => 'CDR-MINI',
            'price' => 149.90,
            'compare' => 199.00,
            'cost' => 76.00,
            'featured' => false,
            'attributes' => [
                'medida' => ['50x70 cm', '70x90 cm'],
            ],
            'images' => [
                'default' => [
                    'https://images.pexels.com/photos/5978721/pexels-photo-5978721.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/4067764/pexels-photo-4067764.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Accesorios
        |--------------------------------------------------------------------------
        */
        'cinturon-cuero-clasico' => [
            'category' => 'accesorios/cinturones',
            'brand' => 'kurama',
            'name' => 'Cinturón de Cuero Clásico',
            'short' => 'Cinturón de cuero vacuno con hebilla metálica regulable.',
            'description' => 'Cinturón de cuero vacuno curtido de 4 mm con hebilla metálica regulable y remaches reforzados. El cuero se suaviza con el uso y se adapta a cualquier pantalón formal o de diario.',
            'sku' => 'CIN-CUER',
            'price' => 99.90,
            'compare' => 139.00,
            'cost' => 48.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Marrón'],
                'medida' => ['90 cm', '100 cm', '110 cm'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/38053161/pexels-photo-38053161.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/38053201/pexels-photo-38053201.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Marrón' => [
                    'https://images.pexels.com/photos/35322147/pexels-photo-35322147.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/35057394/pexels-photo-35057394.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'mochila-urban-canvas' => [
            'category' => 'accesorios/bolsas-y-mochilas',
            'brand' => 'kurama',
            'name' => 'Mochila Urban Canvas',
            'short' => 'Mochila de lona con compartimento para laptop de 15".',
            'description' => 'Mochila de lona de 600D con compartimento acolchado para laptop de 15", bolsillo frontal con cremallera y tirantes ergonómicos. Tela resistente al agua y base reforzada.',
            'sku' => 'MOC-CANVAS',
            'price' => 129.90,
            'compare' => 179.00,
            'cost' => 62.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Beige', 'Verde'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/3731256/pexels-photo-3731256.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/13869858/pexels-photo-13869858.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Beige' => [
                    'https://images.pexels.com/photos/8004822/pexels-photo-8004822.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/6033793/pexels-photo-6033793.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Verde' => [
                    'https://images.pexels.com/photos/18510444/pexels-photo-18510444.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/2081199/pexels-photo-2081199.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'bolso-cuero-mujer' => [
            'category' => 'accesorios/bolsas-y-mochilas',
            'brand' => 'marbell',
            'name' => 'Bolso de Cuero para Mujer',
            'short' => 'Bolso de cuero con compartimento acolchado para laptop y cierre de cremallera.',
            'description' => 'Bolso tote de cuero con compartimento acolchado para laptop de 14", bolsillo interior con cremallera y base reforzada. Correas de hombro ajustables y un compartimento posterior para acceso rápido.',
            'sku' => 'BOL-MUJER',
            'price' => 259.90,
            'compare' => 349.00,
            'cost' => 146.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Marrón'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/12428373/pexels-photo-12428373.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/31450721/pexels-photo-31450721.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Marrón' => [
                    'https://images.pexels.com/photos/27204287/pexels-photo-27204287.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/22434757/pexels-photo-22434757.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Tecnología
        |--------------------------------------------------------------------------
        */
        'audifonos-bluetooth-anc' => [
            'category' => 'tecnologia/audio',
            'brand' => 'nimbus',
            'name' => 'Audífonos Bluetooth con ANC',
            'short' => 'Audífonos over-ear con cancelación activa de ruido y 40 h de batería.',
            'description' => 'Audífonos over-ear con cancelación activa de ruido híbrida, 40 horas de reproducción y carga rápida (10 min = 6 h). Bluetooth 5.3 multipunto con códecs AAC y LDAC, e incluye estuche rígido.',
            'sku' => 'AUD-ANC',
            'price' => 399.90,
            'compare' => 549.00,
            'cost' => 228.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Blanco'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/33174697/pexels-photo-33174697.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/30428610/pexels-photo-30428610.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Blanco' => [
                    'https://images.pexels.com/photos/36230830/pexels-photo-36230830.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/17664053/pexels-photo-17664053.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'parlante-bluetooth-portatil' => [
            'category' => 'tecnologia/audio',
            'brand' => 'nimbus',
            'name' => 'Parlante Bluetooth Portátil',
            'short' => 'Parlante portátil IPX7 con 20 h de batería y estéreo verdadero.',
            'description' => 'Parlante Bluetooth portátil con protección IPX7 (sumergible), 20 horas de batería y emparejamiento estéreo verdadero. Radiador pasivo para graves más profundos y micrófono integrado para llamadas.',
            'sku' => 'PAR-BT',
            'price' => 249.90,
            'compare' => 339.00,
            'cost' => 142.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Azul'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/31748137/pexels-photo-31748137.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/32300575/pexels-photo-32300575.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Azul' => [
                    'https://images.pexels.com/photos/27129211/pexels-photo-27129211.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/29617989/pexels-photo-29617989.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'camara-accion-4k' => [
            'category' => 'tecnologia/camaras',
            'brand' => 'nimbus',
            'name' => 'Cámara de Acción 4K',
            'short' => 'Cámara de acción 4K con estabilización y funda estanca 30 m.',
            'description' => 'Cámara de acción con video 4K a 60 fps, estabilización electrónica y sensor de 1/1.7". Incluye funda estanca para 30 m, soporte para casco y mango de mano. Doble pantalla para encuadrar con facilidad.',
            'sku' => 'CAM-ACC',
            'price' => 549.90,
            'compare' => 749.00,
            'cost' => 318.00,
            'featured' => true,
            'attributes' => [
                'color' => ['Negro', 'Gris'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/36996854/pexels-photo-36996854.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/29623068/pexels-photo-29623068.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Gris' => [
                    'https://images.pexels.com/photos/38459943/pexels-photo-38459943.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/19297707/pexels-photo-19297707.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'lampara-led-mesa' => [
            'category' => 'tecnologia/iluminacion',
            'brand' => 'volt',
            'name' => 'Lámpara LED de Mesa',
            'short' => 'Lámpara LED regulable con tres temperaturas de color y base USB.',
            'description' => 'Lámpara de escritorio LED con brillo regulable, tres temperaturas de color (3000K a 6000K) y fuente de 10 W. La base incluye un puerto USB para cargar el teléfono y el brazo se orienta con facilidad.',
            'sku' => 'LAM-LED',
            'price' => 139.90,
            'compare' => 189.00,
            'cost' => 72.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Blanco'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/31726664/pexels-photo-31726664.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/15438344/pexels-photo-15438344.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Blanco' => [
                    'https://images.pexels.com/photos/28859311/pexels-photo-28859311.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/19354255/pexels-photo-19354255.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'power-bank-20000' => [
            'category' => 'tecnologia/power',
            'brand' => 'volt',
            'name' => 'Power Bank 20000 mAh',
            'short' => 'Batería externa de 20000 mAh con carga rápida 22.5 W y dos puertos.',
            'description' => 'Batería externa de 20000 mAh con carga rápida de 22.5 W, dos puertos USB-A y un puerto USB-C. Incluye pantalla digital con el porcentaje de carga restante y protección contra sobrecarga.',
            'sku' => 'PWB-20K',
            'price' => 169.90,
            'compare' => 229.00,
            'cost' => 88.00,
            'featured' => false,
            'attributes' => [
                'color' => ['Negro', 'Blanco'],
            ],
            'images' => [
                'Negro' => [
                    'https://images.pexels.com/photos/34338614/pexels-photo-34338614.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/38354917/pexels-photo-38354917.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
                'Blanco' => [
                    'https://images.pexels.com/photos/37475677/pexels-photo-37475677.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/17800265/pexels-photo-17800265.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
        'cargador-gan-65w' => [
            'category' => 'tecnologia/power',
            'brand' => 'volt',
            'name' => 'Cargador GaN 65 W',
            'short' => 'Cargador de pared GaN de 65 W con dos puertos USB-C y un USB-A.',
            'description' => 'Cargador de pared con tecnología GaN de 65 W: dos puertos USB-C (PD 3.0) y un USB-A. Tamaño compacto con protección contra sobrecalentamiento, ideal para viajar con laptop y teléfono.',
            'sku' => 'CRG-GAN',
            'price' => 129.90,
            'compare' => 179.00,
            'cost' => 64.00,
            'featured' => false,
            'attributes' => [],
            'images' => [
                'default' => [
                    'https://images.pexels.com/photos/29407254/pexels-photo-29407254.jpeg?auto=compress&cs=tinysrgb&w=800',
                    'https://images.pexels.com/photos/36012993/pexels-photo-36012993.jpeg?auto=compress&cs=tinysrgb&w=800',
                ],
            ],
        ],
    ];

    /**
     * Marcas del catálogo demo.
     *
     * @var array<string, string>
     */
    private const BRANDS = [
        'kurama' => 'Kurama',
        'nordis' => 'Nordis',
        'alba' => 'Alba',
        'marbell' => 'Marbell',
        'stride' => 'Stride',
        'terra' => 'Terra',
        'vitacasa' => 'Vitacasa',
        'aurora' => 'Aurora Home',
        'nimbus' => 'Nimbus',
        'volt' => 'Volt',
    ];

    public function run(): void
    {
        DB::beginTransaction();

        try {
            $suppliers = $this->seedSuppliers();
            $brands = $this->seedBrands();
            $categories = $this->seedCategories();
            $attributeValues = $this->seedAttributes();

            foreach (self::CATALOG as $slug => $product) {
                if (Product::where('slug', $slug)->exists()) {
                    $this->command->warn("Producto ya existe, omitido: {$slug}");

                    continue;
                }

                $this->seedProduct(
                    slug: $slug,
                    data: $product,
                    suppliers: $suppliers,
                    brands: $brands,
                    categories: $categories,
                    attributeValues: $attributeValues,
                );
            }

            DB::commit();

            $this->command->info(sprintf(
                '✅ Catálogo de demostración creado: %d productos, %d variantes, %d imágenes.',
                Product::count(),
                ProductVariant::count(),
                ProductImage::count(),
            ));
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->command->error('❌ Error al crear el catálogo.');
            $this->command->error($e->getMessage());

            throw $e;
        }
    }

    /**
     * @return array<int, Supplier>
     */
    private function seedSuppliers(): array
    {
        $andinas = Supplier::withTrashed()->updateOrCreate(
            ['code' => 'SUP000001'],
            [
                'business_name' => 'Distribuciones Andinas S.A.C.',
                'trade_name' => 'Distribuciones Andinas',
                'tax_id' => '20100123456',
                'contact_name' => 'Rosa Quispe',
                'email' => 'ventas@distribucionesandinas.pe',
                'phone' => '+51 987 654 321',
                'estimated_dispatch_days' => 3,
                'status' => 'active',
            ],
        );

        $import = Supplier::withTrashed()->updateOrCreate(
            ['code' => 'SUP000002'],
            [
                'business_name' => 'Importaciones Lima Norte S.A.C.',
                'trade_name' => 'ILN Import',
                'tax_id' => '20500987654',
                'contact_name' => 'Marco Medina',
                'email' => 'hola@ilnimport.pe',
                'phone' => '+51 912 345 678',
                'estimated_dispatch_days' => 5,
                'status' => 'active',
            ],
        );

        return [$andinas, $import];
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(): array
    {
        $brands = [];

        foreach (self::BRANDS as $slug => $name) {
            $brands[$slug] = Brand::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true],
            );
        }

        return $brands;
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [];

        foreach (Category::whereNotNull('parent_id')->get() as $category) {
            $categories[$category->path] = $category;
        }

        foreach (Category::whereNull('parent_id')->get() as $category) {
            $categories[$category->path] = $category;
        }

        foreach (array_keys(self::CATALOG) as $slug) {
            $path = self::CATALOG[$slug]['category'];

            if (! isset($categories[$path])) {
                throw new \RuntimeException("La categoría «{$path}» del producto «{$slug}» no existe. Ejecuta CategorySeeder primero.");
            }
        }

        return $categories;
    }

    /**
     * Crea los atributos del catálogo y devuelve sus valores indexados por
     * `slug de atributo` y `slug de valor`.
     *
     * @return array<string, array<string, AttributeValue>>
     */
    private function seedAttributes(): array
    {
        $values = [];

        $color = Attribute::withTrashed()->firstOrNew(['slug' => 'color']);
        $color->fill([
            'name' => 'Color',
            'type' => 'color',
            'is_filter' => true,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        if ($color->trashed()) {
            $color->restore();
        }
        $color->save();

        foreach (self::COLORS as $name => $hex) {
            $value = $this->createAttributeValue($color, $name, $hex, array_search($name, array_keys(self::COLORS), true) + 1);
            $values['color'][Str::slug($name)] = $value;
        }

        $definitions = [
            'talla' => ['Talla', 2],
            'talla-calzado' => ['Talla de Calzado', 3],
            'medida' => ['Medida', 4],
        ];

        foreach ($definitions as $slug => [$name, $sortOrder]) {
            $attribute = Attribute::withTrashed()->firstOrNew(['slug' => $slug]);
            $attribute->fill([
                'name' => $name,
                'type' => 'select',
                'is_filter' => true,
                'is_required' => true,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
            if ($attribute->trashed()) {
                $attribute->restore();
            }
            $attribute->save();

            foreach (self::ATTRIBUTE_VALUES[$slug] as $index => $valueName) {
                $value = $this->createAttributeValue($attribute, $valueName, null, $index + 1);
                $values[$slug][Str::slug($valueName)] = $value;
            }
        }

        return $values;
    }

    private function createAttributeValue(Attribute $attribute, string $value, ?string $color, int $sortOrder): AttributeValue
    {
        $av = AttributeValue::withTrashed()->firstOrNew(['attribute_id' => $attribute->id, 'slug' => Str::slug($value)]);
        $av->fill([
            'value' => $value,
            'color' => $color,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
        if ($av->trashed()) {
            $av->restore();
        }
        $av->save();

        return $av;
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     * @param  array<string, Brand>  $brands
     * @param  array<string, Category>  $categories
     * @param  array<string, array<string, AttributeValue>>  $attributeValues
     */
    private function seedProduct(
        string $slug,
        array $data,
        array $suppliers,
        array $brands,
        array $categories,
        array $attributeValues,
    ): void {
        $product = Product::create([
            'category_id' => $categories[$data['category']]->id,
            'brand_id' => $brands[$data['brand']]->id,
            'name' => $data['name'],
            'slug' => $slug,
            'short_description' => $data['short'],
            'description' => $data['description'],
            'status' => true,
            'is_featured' => $data['featured'],
            'is_visible' => true,
            'seo_title' => $data['name'].' | Brevare',
            'seo_description' => $data['short'],
        ]);

        // Aplica un descuento permanente y conserva el precio base para mostrarlo tachado.
        $discountPercent = random_int(5, 30);
        $comparePrice = $data['price'];
        $salePrice = round($comparePrice * (1 - ($discountPercent / 100)), 2);

        $combinations = $this->combinations($data['attributes']);

        foreach ($combinations as $index => $combination) {
            $sku = $this->skuFor($data['sku'], $combination);
            $colorName = $combination['color'] ?? null;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'cost_price' => $data['cost'],
                'sale_price' => $salePrice,
                'compare_price' => $comparePrice,
                'discount_percent' => $discountPercent,
                'weight' => 0.5,
                'is_default' => $index === 0,
                'is_active' => true,
            ]);

            foreach ($combination as $attributeSlug => $valueSlug) {
                $attribute = Attribute::where('slug', $attributeSlug)->firstOrFail();
                $value = $attributeValues[$attributeSlug][$valueSlug] ?? null;

                if (! $value) {
                    throw new \RuntimeException("Valor «{$valueSlug}» no encontrado para el atributo «{$attributeSlug}».");
                }

                ProductVariantAttributeValue::create([
                    'product_variant_id' => $variant->id,
                    'attribute_id' => $attribute->id,
                    'attribute_value_id' => $value->id,
                ]);
            }

            $this->seedSupplierVariants($variant, $data, $sku, $suppliers);
            $this->seedImages($product, $variant, $data, $colorName);
        }
    }

    /**
     * @param  array<string, Supplier>  $suppliers
     */
    private function seedSupplierVariants(ProductVariant $variant, array $data, string $sku, array $suppliers): void
    {
        $primary = $suppliers[array_key_first($suppliers)];
        $secondary = $suppliers[1] ?? null;

        $rows = [
            [
                'supplier' => $primary,
                'stock' => 8 + (crc32($sku) % 33),
                'shipping_cost' => 9.90,
                'dispatch' => $primary->estimated_dispatch_days,
            ],
        ];

        if ($secondary && (crc32($sku) % 3) !== 0) {
            $rows[] = [
                'supplier' => $secondary,
                'stock' => 3 + (crc32($sku.'-alt') % 18),
                'shipping_cost' => 12.90,
                'dispatch' => $secondary->estimated_dispatch_days,
            ];
        }

        foreach ($rows as $index => $row) {
            SupplierVariant::create([
                'supplier_id' => $row['supplier']->id,
                'product_variant_id' => $variant->id,
                'supplier_sku' => 'SV-'.$sku,
                'cost_price' => $data['cost'],
                'shipping_cost' => $row['shipping_cost'],
                'stock' => $row['stock'],
                'reserved_stock' => 0,
                'minimum_stock' => 2,
                'estimated_dispatch_days' => $row['dispatch'],
                'is_default' => $index === 0,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedImages(Product $product, ProductVariant $variant, array $data, ?string $colorName): void
    {
        $urls = $colorName !== null
            ? ($data['images'][$colorName] ?? [])
            : ($data['images']['default'] ?? []);

        if ($urls === []) {
            $urls = $data['images'][array_key_first($data['images'])] ?? [];
        }

        foreach (array_values($urls) as $index => $url) {
            $publicId = strtolower(Str::slug($product->name.'-'.$variant->sku)).($index > 0 ? '-'.($index + 1) : '');

            ProductImage::create([
                'product_variant_id' => $variant->id,
                'public_id' => $publicId,
                'file_name' => $publicId.'.jpg',
                'url' => $url,
                'secure_url' => $url,
                'format' => 'jpeg',
                'size' => 0,
                'width' => 800,
                'height' => 800,
                'is_primary' => $index === 0,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Genera el producto cartesiano de los grupos de atributos declarados.
     *
     * @param  array<string, array<int, string>>  $groups
     * @return array<int, array<string, string>>
     */
    private function combinations(array $groups): array
    {
        if ($groups === []) {
            return [[]];
        }

        $combinations = [[]];

        foreach ($groups as $attributeSlug => $values) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = $combination + [$attributeSlug => Str::slug($value)];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  array<string, string>  $combination
     */
    private function skuFor(string $prefix, array $combination): string
    {
        $suffix = collect($combination)
            ->map(fn ($valueSlug) => Str::upper($valueSlug))
            ->implode('-');

        return $suffix === '' ? $prefix : $prefix.'-'.$suffix;
    }
}
