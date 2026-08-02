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
        Schema::create('product_variants', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Identificación
            |--------------------------------------------------------------------------
            */

            $table->id()
                ->comment('Identificador único de la variante.');

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Producto al que pertenece la variante.');

            $table->string('sku', 100)
                ->unique()
                ->comment('SKU único de la variante.');

            $table->string('barcode', 100)
                ->nullable()
                ->comment('Código de barras.');

            /*
            |--------------------------------------------------------------------------
            | Precios
            |--------------------------------------------------------------------------
            */

            $table->decimal('cost_price', 10, 2)
                ->default(0)
                ->comment('Costo de adquisición.');

            $table->decimal('sale_price', 10, 2)
                ->default(0)
                ->comment('Precio de venta.');

            $table->decimal('compare_price', 10, 2)
                ->nullable()
                ->comment('Precio referencial o anterior para promociones.');

            /*
            |--------------------------------------------------------------------------
            | Peso y dimensiones
            |--------------------------------------------------------------------------
            */

            $table->decimal('weight', 8, 2)
                ->nullable()
                ->comment('Peso en kilogramos.');

            $table->decimal('length', 8, 2)
                ->nullable()
                ->comment('Largo en centímetros.');

            $table->decimal('width', 8, 2)
                ->nullable()
                ->comment('Ancho en centímetros.');

            $table->decimal('height', 8, 2)
                ->nullable()
                ->comment('Alto en centímetros.');

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_default')
                ->default(false)
                ->comment('Indica si es la variante principal.');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado de la variante.');

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

            $table->index('product_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
