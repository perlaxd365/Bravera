<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Token de Culqi (tkn_..., ype_...) o identificador de la orden
            // (ord_...). Necesario para conciliar y reintentar sin volver a cobrar.
            $table->string('source_id', 100)
                ->nullable()
                ->after('gateway_transaction_id')
                ->comment('Token u orden creada en la pasarela.');

            $table->text('checkout_url')
                ->nullable()
                ->after('source_id')
                ->comment('URL de pago para métodos asíncronos (Yape/QR).');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'checkout_url']);
        });
    }
};
