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
        Schema::create('product_variant_stocks', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Identificación
            |--------------------------------------------------------------------------
            */

            $table->id()
                ->comment('Identificador único del registro de stock.');

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

            /*
            |--------------------------------------------------------------------------
            | Inventario
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('stock')
                ->default(0)
                ->comment('Stock disponible.');

            $table->unsignedInteger('reserved_stock')
                ->default(0)
                ->comment('Stock reservado por pedidos pendientes.');

            $table->unsignedInteger('minimum_stock')
                ->default(0)
                ->comment('Stock mínimo permitido.');

            /*
            |--------------------------------------------------------------------------
            | Sincronización
            |--------------------------------------------------------------------------
            */

            $table->timestamp('last_sync_at')
                ->nullable()
                ->comment('Última sincronización del stock.');

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('manage_stock')
                ->default(true)
                ->comment('Indica si la variante controla inventario.');

            $table->boolean('allow_backorders')
                ->default(false)
                ->comment('Permite vender sin stock disponible.');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado del registro.');

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

            $table->unique('product_variant_id');

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('stock');
            $table->index('reserved_stock');
            $table->index('manage_stock');
            $table->index('is_active');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_stocks');
    }
};