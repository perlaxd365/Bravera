<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Taxonomía del catálogo demo: 5 familias y 16 subcategorías.
     *
     * Los `slug` son explícitos porque el storefront navega por `path`
     * (por ejemplo `moda/mujer`, `hogar/cocina`, `tecnologia`).
     *
     * @var array<int, array<string, mixed>>
     */
    private const TREE = [
        [
            'name' => 'Hombre',
            'slug' => 'hombre',
            'description' => 'Moda, calzado y accesorios para hombre.',
            'position' => 1,
            'is_featured' => true,
            'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=1200&q=85',
            'children' => [],
        ],
        [
            'name' => 'Mujer',
            'slug' => 'mujer',
            'description' => 'Moda, calzado y accesorios para mujer.',
            'position' => 2,
            'is_featured' => true,
            'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=85',
            'children' => [],
        ],
        [
            'name' => 'Niño',
            'slug' => 'nino',
            'description' => 'Ropa, calzado y novedades para niños.',
            'position' => 3,
            'is_featured' => true,
            'image' => 'https://images.unsplash.com/photo-1503919005314-30d93d07d823?auto=format&fit=crop&w=1200&q=85',
            'children' => [],
        ],
        [
            'name' => 'Niña',
            'slug' => 'nina',
            'description' => 'Ropa, calzado y novedades para niñas.',
            'position' => 4,
            'is_featured' => true,
            'image' => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?auto=format&fit=crop&w=1200&q=85',
            'children' => [],
        ],
        [
            'name' => 'Moda',
            'slug' => 'moda',
            'description' => 'Prendas de moda para mujer y hombre.',
            'position' => 1,
            'is_featured' => true,
            'children' => [
                [
                    'name' => 'Moda Hombre',
                    'slug' => 'hombre',
                    'description' => 'Polos, camisas, pantalones y outerwear masculino.',
                ],
                [
                    'name' => 'Moda Mujer',
                    'slug' => 'mujer',
                    'description' => 'Blusas, vestidos, denim y abrigos femeninos.',
                ],
            ],
        ],
        [
            'name' => 'Calzado',
            'slug' => 'calzado',
            'description' => 'Sneakers, zapatillas, botas, sandalias y mocasines.',
            'position' => 2,
            'is_featured' => true,
            'children' => [
                [
                    'name' => 'Sneakers',
                    'slug' => 'sneakers',
                    'description' => 'Sneakers urbanos y deportivos.',
                ],
                [
                    'name' => 'Zapatillas',
                    'slug' => 'zapatillas',
                    'description' => 'Zapatillas ligeras para el día a día.',
                ],
                [
                    'name' => 'Botas',
                    'slug' => 'botas',
                    'description' => 'Botas de cuero para temporada fría.',
                ],
                [
                    'name' => 'Sandalias',
                    'slug' => 'sandalias',
                    'description' => 'Sandalias con plantilla ergonómica.',
                ],
                [
                    'name' => 'Mocasines',
                    'slug' => 'mocasines',
                    'description' => 'Mocasines de cuero y materiales premium.',
                ],
            ],
        ],
        [
            'name' => 'Hogar',
            'slug' => 'hogar',
            'description' => 'Decoración, organización y descanso para el hogar.',
            'position' => 3,
            'is_featured' => false,
            'children' => [
                [
                    'name' => 'Cocina',
                    'slug' => 'cocina',
                    'description' => 'Vajilla y utensilios para cocinar y servir.',
                ],
                [
                    'name' => 'Dormitorio',
                    'slug' => 'dormitorio',
                    'description' => 'Sábanas, almohadas y cobertores.',
                ],
                [
                    'name' => 'Decoración',
                    'slug' => 'decoracion',
                    'description' => 'Cuadros, espejos y objetos de decoración.',
                ],
                [
                    'name' => 'Organización',
                    'slug' => 'organizacion',
                    'description' => 'Cajas y soluciones de orden.',
                ],
            ],
        ],
        [
            'name' => 'Tecnología',
            'slug' => 'tecnologia',
            'description' => 'Audio, cámaras, iluminación y energía portátil.',
            'position' => 4,
            'is_featured' => true,
            'children' => [
                [
                    'name' => 'Audio',
                    'slug' => 'audio',
                    'description' => 'Audífonos y parlantes bluetooth.',
                ],
                [
                    'name' => 'Cámaras',
                    'slug' => 'camaras',
                    'description' => 'Cámaras de acción y sus accesorios.',
                ],
                [
                    'name' => 'Iluminación',
                    'slug' => 'iluminacion',
                    'description' => 'Luces LED y lámparas decorativas.',
                ],
                [
                    'name' => 'Power',
                    'slug' => 'power',
                    'description' => 'Baterías externas, cargadores y cables.',
                ],
            ],
        ],
        [
            'name' => 'Accesorios',
            'slug' => 'accesorios',
            'description' => 'Accesorios de uso diario y regalo.',
            'position' => 5,
            'is_featured' => false,
            'children' => [
                [
                    'name' => 'Cinturones',
                    'slug' => 'cinturones',
                    'description' => 'Cinturones de cuero con hebilla metálica.',
                ],
                [
                    'name' => 'Bolsas y Mochilas',
                    'slug' => 'bolsas-y-mochilas',
                    'description' => 'Mochilas, tote bags y crossbody.',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        Category::query()->delete();

        foreach (self::TREE as $parentData) {
            $parent = Category::withTrashed()->firstOrNew(['path' => $parentData['slug']]);
            $parent->fill([
                'name' => $parentData['name'],
                'slug' => $parentData['slug'],
                'description' => $parentData['description'],
                'position' => $parentData['position'],
                'is_visible' => true,
                'is_featured' => $parentData['is_featured'],
                'meta_title' => $parentData['name'].' | Brevare',
                'meta_description' => $parentData['description'],
            ]);
            if (blank($parent->image)) {
                $parent->image = $parentData['image'] ?? null;
            }
            if ($parent->trashed()) {
                $parent->restore();
            }
            $parent->save();

            $position = 1;

            foreach ($parentData['children'] as $childData) {
                $childPath = $parentData['slug'].'/'.$childData['slug'];

                $child = Category::withTrashed()->firstOrNew(['path' => $childPath]);
                $child->fill([
                    'parent_id' => $parent->id,
                    'name' => $childData['name'],
                    'slug' => $childData['slug'],
                    'description' => $childData['description'],
                    'position' => $position++,
                    'is_visible' => true,
                    'is_featured' => false,
                    'meta_title' => $childData['name'].' | Brevare',
                    'meta_description' => $childData['description'],
                ]);
                if ($child->trashed()) {
                    $child->restore();
                }
                $child->save();
            }
        }

        $this->command->info('✅ Categorías creadas correctamente.');
    }
}
