<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();
            $table->json('hero_slides')->nullable();
            $table->json('section_texts')->nullable();
            $table->json('rotating_phrases')->nullable();
            $table->timestamps();
        });

        $now = now();
        $categories = [
            ['name' => 'Hombre', 'slug' => 'hombre', 'description' => 'Moda, calzado y accesorios para hombre.', 'position' => 1, 'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=1200&q=85'],
            ['name' => 'Mujer', 'slug' => 'mujer', 'description' => 'Moda, calzado y accesorios para mujer.', 'position' => 2, 'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=85'],
            ['name' => 'Niño', 'slug' => 'nino', 'description' => 'Ropa, calzado y novedades para niños.', 'position' => 3, 'image' => 'https://images.unsplash.com/photo-1503919005314-30d93d07d823?auto=format&fit=crop&w=1200&q=85'],
            ['name' => 'Niña', 'slug' => 'nina', 'description' => 'Ropa, calzado y novedades para niñas.', 'position' => 4, 'image' => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?auto=format&fit=crop&w=1200&q=85'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['path' => $category['slug']],
                [
                    'parent_id' => null,
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'path' => $category['slug'],
                    'description' => $category['description'],
                    'image' => $category['image'],
                    'icon' => 'tag',
                    'position' => $category['position'],
                    'is_visible' => true,
                    'is_featured' => true,
                    'meta_title' => $category['name'].' | Brevare',
                    'meta_description' => $category['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Las categorías pueden haber recibido productos o fotos personalizadas;
        // se conservan al revertir la tabla editable para proteger esos datos.
        Schema::dropIfExists('homepage_settings');
    }
};
