<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_intent', 30)
                ->nullable()
                ->after('payment_status')
                ->index()
                ->comment('Pasarela elegida al iniciar el pago: culqi | izipay | mercadopago.');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_intent');
        });
    }
};
