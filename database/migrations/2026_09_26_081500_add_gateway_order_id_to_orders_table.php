<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            /*
            |----------------------------------------------------------------------
            | Orden de la pasarela
            |----------------------------------------------------------------------
            |
            | Cuando el pago se completa dentro del modal del proveedor (Culqi
            | Checkout Custom) la orden se crea ANTES de cobrar, porque los
            | métodos asíncronos no funcionan sin una orden previa. Se guarda aquí
            | su identificador (ord_...) para poder conciliar el pedido con el
            | objeto de Culqi, y para que el webhook encuentre el pago aunque el
            | comprador cierre el navegador.
            |
            | Es nullable: los pagos con tarjeta se cobran con un cargo (chr_...)
            | y nunca pasan por aquí.
            |
            */

            $table->string('gateway_order_id', 40)
                ->nullable()
                ->index()
                ->comment('Identificador de la orden en la pasarela (ord_... de Culqi).');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['gateway_order_id']);
            $table->dropColumn('gateway_order_id');
        });
    }
};
