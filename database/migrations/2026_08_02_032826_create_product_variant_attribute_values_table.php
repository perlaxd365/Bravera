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
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Identificación
            |--------------------------------------------------------------------------
            */

            $table->id()
                ->comment('Identificador único del registro.');

            /*
            |--------------------------------------------------------------------------
            | Relaciones
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Variante del producto.');

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Atributo.');

            $table->foreignId('attribute_value_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Valor seleccionado para el atributo.');

            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Restricciones
            |--------------------------------------------------------------------------
            */

            $table->unique(
                [
                    'product_variant_id',
                    'attribute_id',
                ],
                'pvav_variant_attribute_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('product_variant_id');
            $table->index('attribute_id');
            $table->index('attribute_value_id');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
    }
};
