<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete()
                ->comment('Pedido del cliente que origina la orden.');

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->string('supplier_order_number', 40)
                ->unique()
                ->comment('Número de orden interna / para el proveedor.');

            /*
            |--------------------------------------------------------------------------
            | Estados y montos
            |--------------------------------------------------------------------------
            */

            $table->string('status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | sent | accepted | in_transit | delivered | cancelled');

            $table->decimal('total_cost', 10, 2)
                ->default(0)
                ->comment('Costo total (unit_cost + shipping) de los items.');

            $table->string('tracking_code', 100)
                ->nullable()
                ->comment('Código de seguimiento proporcionado por el proveedor.');

            $table->string('tracking_url')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->dateTime('sent_at')
                ->nullable();

            $table->dateTime('delivered_at')
                ->nullable();

            $table->timestamp('submitted_at')
                ->nullable()
                ->comment('Momento en que se generó la orden al proveedor.');

            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['order_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_orders');
    }
};
