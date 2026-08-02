<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Identificación
            |--------------------------------------------------------------------------
            */

            $table->id()
                ->comment('Identificador único de la imagen.');

            /*
            |--------------------------------------------------------------------------
            | Relaciones
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Variante a la que pertenece la imagen.');

            /*
            |--------------------------------------------------------------------------
            | Información del archivo
            |--------------------------------------------------------------------------
            */

            $table->string('public_id', 255)
                ->comment('Identificador del archivo en Cloudinary.');

            $table->string('file_name', 255)
                ->nullable()
                ->comment('Nombre original del archivo.');

            $table->string('url')
                ->comment('URL pública de la imagen.');

            $table->string('secure_url')
                ->nullable()
                ->comment('URL HTTPS de Cloudinary.');

            $table->string('format', 20)
                ->nullable()
                ->comment('Formato del archivo.');

            $table->unsignedInteger('size')
                ->nullable()
                ->comment('Tamaño del archivo en bytes.');

            $table->unsignedSmallInteger('width')
                ->nullable()
                ->comment('Ancho de la imagen en píxeles.');

            $table->unsignedSmallInteger('height')
                ->nullable()
                ->comment('Alto de la imagen en píxeles.');

            /*
            |--------------------------------------------------------------------------
            | Organización
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_primary')
                ->default(false)
                ->comment('Indica si es la imagen principal.');

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Orden de visualización.');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado de la imagen.');

            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('product_variant_id');
            $table->index('public_id');
            $table->index('is_primary');
            $table->index('sort_order');
            $table->index('is_active');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};