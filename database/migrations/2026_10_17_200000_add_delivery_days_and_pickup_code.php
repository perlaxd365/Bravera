<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_days')
                ->nullable()
                ->comment('Días hábiles estimados de entrega para esta tarifa.');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_days')
                ->nullable()
                ->comment('Mayor plazo de entrega de las tarifas del pedido.');

            $table->string('pickup_code', 4)
                ->nullable()
                ->index()
                ->comment('Código de 4 dígitos para recoger el pedido pagado.');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_days', 'pickup_code']);
        });

        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropColumn('delivery_days');
        });
    }
};
