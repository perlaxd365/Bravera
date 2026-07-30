<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Limpiar tabla
            |--------------------------------------------------------------------------
            */

            Category::query()->delete();

            /*
            |--------------------------------------------------------------------------
            | Categorías principales
            |--------------------------------------------------------------------------
            */

            $technology = Category::create([
                'name' => 'Tecnología',
                'slug' => 'tecnologia',
                'path' => 'tecnologia',
                'description' => 'Productos tecnológicos.',
                'position' => 1,
                'is_visible' => true,
                'is_featured' => true,
                'meta_title' => 'Tecnología',
                'meta_description' => 'Productos tecnológicos.',
            ]);

            $fashion = Category::create([
                'name' => 'Moda',
                'slug' => 'moda',
                'path' => 'moda',
                'description' => 'Ropa y accesorios.',
                'position' => 2,
                'is_visible' => true,
                'is_featured' => true,
                'meta_title' => 'Moda',
                'meta_description' => 'Ropa y accesorios.',
            ]);

            $home = Category::create([
                'name' => 'Hogar',
                'slug' => 'hogar',
                'path' => 'hogar',
                'description' => 'Artículos para el hogar.',
                'position' => 3,
                'is_visible' => true,
                'is_featured' => false,
                'meta_title' => 'Hogar',
                'meta_description' => 'Artículos para el hogar.',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Tecnología
            |--------------------------------------------------------------------------
            */

            Category::create([
                'parent_id' => $technology->id,
                'name' => 'Celulares',
                'slug' => 'celulares',
                'path' => 'tecnologia/celulares',
                'position' => 1,
                'is_visible' => true,
            ]);

            Category::create([
                'parent_id' => $technology->id,
                'name' => 'Laptops',
                'slug' => 'laptops',
                'path' => 'tecnologia/laptops',
                'position' => 2,
                'is_visible' => true,
            ]);

            Category::create([
                'parent_id' => $technology->id,
                'name' => 'Tablets',
                'slug' => 'tablets',
                'path' => 'tecnologia/tablets',
                'position' => 3,
                'is_visible' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Moda
            |--------------------------------------------------------------------------
            */

            Category::create([
                'parent_id' => $fashion->id,
                'name' => 'Hombre',
                'slug' => 'hombre',
                'path' => 'moda/hombre',
                'position' => 1,
                'is_visible' => true,
            ]);

            Category::create([
                'parent_id' => $fashion->id,
                'name' => 'Mujer',
                'slug' => 'mujer',
                'path' => 'moda/mujer',
                'position' => 2,
                'is_visible' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Hogar
            |--------------------------------------------------------------------------
            */

            Category::create([
                'parent_id' => $home->id,
                'name' => 'Cocina',
                'slug' => 'cocina',
                'path' => 'hogar/cocina',
                'position' => 1,
                'is_visible' => true,
            ]);

            Category::create([
                'parent_id' => $home->id,
                'name' => 'Dormitorio',
                'slug' => 'dormitorio',
                'path' => 'hogar/dormitorio',
                'position' => 2,
                'is_visible' => true,
            ]);

            DB::commit();

            $this->command->info('✅ Categorías creadas correctamente.');
        } catch (\Throwable $e) {

            DB::rollBack();

            $this->command->error('❌ Error al crear categorías.');
            $this->command->error($e->getMessage());

            throw $e;
        }
    }
}
