<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete()
                ->comment('Pago reembolsado. Nulo si el pago se eliminó.');

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que registró la solicitud.');

            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que aprobó o rechazó la devolución.');

            $table->decimal('amount', 10, 2)
                ->comment('Monto a devolver. Igual al total del pago si es devolución completa.');

            $table->string('currency', 3)->default('PEN');

            $table->string('reason', 30)
                ->comment('Motivo exigido por Culqi: duplicado | fraudulento | solicitud_comprador.');

            $table->string('status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | processing | completed | rejected | failed');

            $table->text('notes')
                ->nullable()
                ->comment('Detalle de la solicitud.');

            $table->text('resolution_notes')
                ->nullable()
                ->comment('Motivo del rechazo o mensaje devuelto por la pasarela.');

            $table->string('gateway_refund_id', 100)
                ->nullable()
                ->index()
                ->comment('ID de la devolución en la pasarela (ref_... en Culqi).');

            $table->json('gateway_response')
                ->nullable()
                ->comment('Respuesta completa de la pasarela.');

            $table->dateTime('requested_at')->nullable();
            $table->dateTime('validated_at')->nullable()
                ->comment('Momento en que un administrador aprobó o rechazó la solicitud.');
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
