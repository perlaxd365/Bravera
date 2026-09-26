<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number', 40)
                ->unique()
                ->comment('Número público del pedido. Ej: BRV-20260920-0001');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Estados
            |--------------------------------------------------------------------------
            */

            $table->string('status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | confirmed | processing | shipped | delivered | cancelled | refunded');

            $table->string('payment_status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | paid | failed | refunded | cancelled');

            /*
            |--------------------------------------------------------------------------
            | Totales
            |--------------------------------------------------------------------------
            */

            $table->decimal('subtotal', 10, 2)
                ->comment('Suma de items sin descuento ni envío.');

            $table->decimal('shipping_total', 10, 2)
                ->default(0)
                ->comment('Costo de envío para el cliente.');

            $table->decimal('discount_total', 10, 2)
                ->default(0)
                ->comment('Descuento aplicado por cupón.');

            $table->decimal('total', 10, 2)
                ->comment('Monto total del pedido.');

            $table->decimal('cost_total', 10, 2)
                ->default(0)
                ->comment('Costo total de los items (para proveedores).');

            /*
            |--------------------------------------------------------------------------
            | Cupón aplicado
            |--------------------------------------------------------------------------
            */

            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons')
                ->nullOnDelete();

            $table->string('coupon_code', 50)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Snapshots
            |--------------------------------------------------------------------------
            */

            $table->json('customer_snapshot')
                ->nullable()
                ->comment('Nombre, email del cliente al momento de comprar.');

            $table->json('address_snapshot')
                ->nullable()
                ->comment('Dirección completa al momento de comprar.');

            $table->string('currency', 3)
                ->default('PEN');

            $table->text('notes')
                ->nullable();

            $table->dateTime('paid_at')
                ->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
