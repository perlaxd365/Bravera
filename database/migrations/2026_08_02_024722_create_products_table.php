<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Categoría principal');

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Marca');

            $table->string('name', 255)
                ->comment('Nombre del producto');

            $table->string('slug', 255)
                ->unique()
                ->comment('Slug único');

            $table->string('short_description', 500)
                ->nullable()
                ->comment('Descripción corta');

            $table->longText('description')
                ->nullable()
                ->comment('Descripción completa');

            $table->boolean('status')
                ->default(true)
                ->comment('Activo');

            $table->boolean('is_featured')
                ->default(false)
                ->comment('Producto destacado');

            $table->boolean('is_visible')
                ->default(true)
                ->comment('Visible en tienda');

            $table->string('seo_title')
                ->nullable();

            $table->text('seo_description')
                ->nullable();

            $table->timestamps();
            $table->softDeletes()
                ->comment('Eliminación lógica');

            $table->index('name');
            $table->index('status');
            $table->index('is_featured');
            $table->index('is_visible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
