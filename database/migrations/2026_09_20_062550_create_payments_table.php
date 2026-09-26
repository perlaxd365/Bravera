<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Método y proveedor del pago
            |--------------------------------------------------------------------------
            */

            $table->string('gateway', 30)
                ->default('manual')
                ->comment('manual | culqi | mercadopago | ...');

            $table->string('method', 30)
                ->nullable()
                ->comment('card | yape | pago_efectivo | transferencia | efectivo | wallet.');

            $table->string('gateway_transaction_id', 100)
                ->nullable()
                ->unique()
                ->comment('ID de la transacción en la pasarela.');

            /*
            |--------------------------------------------------------------------------
            | Montos y estado
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount', 10, 2)
                ->comment('Monto cobrado.');

            $table->string('currency', 3)
                ->default('PEN');

            $table->string('status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | authorized | paid | failed | refunded | cancelled | expired');

            $table->json('raw_response')
                ->nullable()
                ->comment('Respuesta completa de la pasarela.');

            $table->dateTime('paid_at')
                ->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
