<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->foreignId('supplier_order_id')
                ->nullable()
                ->constrained('supplier_orders')
                ->nullOnDelete();

            $table->string('reference', 60)
                ->nullable()
                ->comment('Número de operación o referencia de pago.');

            /*
            |--------------------------------------------------------------------------
            | Montos
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount', 10, 2)
                ->comment('Monto pagado al proveedor (costo de items + envío).');

            $table->decimal('margin_amount', 10, 2)
                ->default(0)
                ->comment('Margen de Bravera sobre esta orden.');

            $table->string('method', 30)
                ->nullable()
                ->comment('transferencia | efectivo | wallet | otro.');

            $table->string('status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | paid | cancelled');

            $table->dateTime('paid_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->unsignedBigInteger('created_by')
                ->nullable()
                ->comment('Usuario del panel que registró el pago.');

            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
