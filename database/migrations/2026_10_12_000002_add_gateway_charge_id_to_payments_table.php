<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo de Culqi asociado al pago.
     *
     * `gateway_transaction_id` guarda el identificador con el que se cobró, y
     * en los métodos asíncronos de Culqi (billeteras, banca móvil, agentes,
     * Cuotéalo) ese identificador es el de la ORDEN (`ord_...`), no el del
     * cargo. Las devoluciones, en cambio, solo se pueden hacer contra un cargo
     * (`chr_...`, POST /v2/refunds). Como el spec de Culqi no documenta cómo
     * obtener el cargo de una orden, se guarda aparte cuando la propia API lo
     * entrega y, si no, se vincula desde el panel.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_charge_id', 100)
                ->nullable()
                ->after('gateway_transaction_id')
                ->comment('Cargo de Culqi (chr_...) usado para las devoluciones.');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway_charge_id');
        });
    }
};
