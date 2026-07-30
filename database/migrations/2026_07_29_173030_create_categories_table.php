<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {

            $table->id()
                ->comment('Identificador único de la categoría.');

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->comment('Categoría padre. NULL indica una categoría principal.');

            $table->string('name')
                ->comment('Nombre visible de la categoría.');

            $table->string('slug')
                ->comment('Slug de la categoría. Debe ser único dentro de la misma categoría padre.');

            $table->string('path')
                ->unique()
                ->comment('Ruta jerárquica completa. Ejemplo: tecnologia/celulares.');

            $table->text('description')
                ->nullable()
                ->comment('Descripción de la categoría.');

            $table->string('image')
                ->nullable()
                ->comment('Imagen representativa de la categoría.');

            $table->string('icon')
                ->nullable()
                ->comment('Icono de la categoría (Bootstrap Icons, FontAwesome, etc.).');

            $table->unsignedSmallInteger('position')
                ->default(0)
                ->comment('Orden de visualización. Menor número = mayor prioridad.');

            $table->boolean('is_visible')
                ->default(true)
                ->comment('Indica si la categoría es visible para los clientes.');

            $table->boolean('is_featured')
                ->default(false)
                ->comment('Indica si la categoría se muestra como destacada.');

            $table->string('meta_title')
                ->nullable()
                ->comment('Título SEO de la categoría.');

            $table->text('meta_description')
                ->nullable()
                ->comment('Descripción SEO de la categoría.');

            $table->timestamps();

            $table->softDeletes()
                ->comment('Fecha de eliminación lógica (Soft Delete).');

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            // Optimiza consultas por categoría padre.
            $table->index('parent_id');

            // El slug puede repetirse en distintas ramas,
            // pero no dentro de la misma categoría padre.
            $table->unique(['parent_id', 'slug']);

            // El path sí debe ser único.
            $table->index('path');

            // Optimiza ordenamiento.
            $table->index('position');

            // Optimiza filtros por estado.
            $table->index('is_visible');

            // Optimiza consultas de categorías destacadas.
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
