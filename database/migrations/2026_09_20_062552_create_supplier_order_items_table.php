<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_order_id')
                ->constrained('supplier_orders')
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnDelete();

            $table->foreignId('supplier_variant_id')
                ->nullable()
                ->constrained('supplier_variants')
                ->nullOnDelete();

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('unit_cost', 10, 2)
                ->default(0)
                ->comment('Costo de compra al proveedor.');

            $table->decimal('supplier_shipping_cost', 10, 2)
                ->default(0)
                ->comment('Costo de envío del proveedor por unidad.');

            $table->decimal('line_cost_total', 10, 2)
                ->default(0)
                ->comment('(unit_cost + shipping) * quantity.');

            $table->timestamps();

            $table->index('supplier_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_order_items');
    }
};
