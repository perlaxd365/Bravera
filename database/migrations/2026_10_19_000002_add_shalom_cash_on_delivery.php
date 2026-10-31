<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('allows_shalom_cod')
                ->default(false)
                ->after('is_visible')
                ->comment('Permite pagar el envío al recoger en Shalom.');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_payment_method', 30)
                ->default('prepaid')
                ->after('shipping_total')
                ->comment('prepaid | shalom_cod');

            $table->decimal('cod_shipping_amount', 10, 2)
                ->default(0)
                ->after('shipping_payment_method')
                ->comment('Envío que el cliente paga en Shalom al recoger.');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_payment_method', 'cod_shipping_amount']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('allows_shalom_cod');
        });
    }
};
