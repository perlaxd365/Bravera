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
        Schema::create('supplier_products', function (Blueprint $table) {

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

            $table->foreignId('supplier_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Proveedor.');

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Variante del producto.');

            /*
            |--------------------------------------------------------------------------
            | Información del proveedor
            |--------------------------------------------------------------------------
            */

            $table->string('supplier_sku', 100)
                ->nullable()
                ->comment('SKU utilizado por el proveedor.');

            $table->string('supplier_product_url')
                ->nullable()
                ->comment('URL del producto en la plataforma del proveedor.');

            /*
            |--------------------------------------------------------------------------
            | Costos
            |--------------------------------------------------------------------------
            */

            $table->decimal('cost_price', 10, 2)
                ->default(0)
                ->comment('Costo de compra al proveedor.');

            $table->decimal('shipping_cost', 10, 2)
                ->default(0)
                ->comment('Costo de envío del proveedor.');

            /*
            |--------------------------------------------------------------------------
            | Disponibilidad
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('available_stock')
                ->default(0)
                ->comment('Stock informado por el proveedor.');

            $table->unsignedTinyInteger('estimated_dispatch_days')
                ->default(1)
                ->comment('Tiempo estimado de despacho en días.');

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_default')
                ->default(false)
                ->comment('Proveedor principal para esta variante.');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Indica si el proveedor está habilitado.');

            /*
            |--------------------------------------------------------------------------
            | Observaciones
            |--------------------------------------------------------------------------
            */

            $table->text('internal_notes')
                ->nullable()
                ->comment('Observaciones internas.');

            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Restricciones
            |--------------------------------------------------------------------------
            */

            $table->unique(
                [
                    'supplier_id',
                    'product_variant_id'
                ],
                'supplier_variant_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('supplier_id');
            $table->index('product_variant_id');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
