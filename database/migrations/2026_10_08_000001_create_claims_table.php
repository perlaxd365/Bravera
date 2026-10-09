<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique()
                ->comment('Código correlativo del reclamo o queja.');

            $table->string('name', 150)
                ->comment('Nombre completo del consumidor.');

            $table->string('document_type', 20)
                ->comment('Tipo de documento: DNI, CE, RUC o Pasaporte.');

            $table->string('document_number', 20)
                ->comment('Número de documento de identidad.');

            $table->string('email', 150)
                ->comment('Correo electrónico de contacto.');

            $table->string('phone', 30)
                ->comment('Teléfono de contacto.');

            $table->string('address', 255)
                ->comment('Domicilio del consumidor.');

            $table->boolean('is_minor')->default(false)
                ->comment('Indica si el consumidor es menor de edad.');

            $table->string('guardian_name', 150)->nullable()
                ->comment('Nombre del padre o apoderado, si es menor de edad.');

            $table->string('order_number', 100)->nullable()
                ->comment('Número de pedido relacionado, si existe.');

            $table->string('claim_type', 20)
                ->comment('Tipo: reclamo o queja.');

            $table->text('claimed_good')
                ->comment('Bien o servicio materia del reclamo.');

            $table->text('description')
                ->comment('Detalle del reclamo o queja.');

            $table->text('request')
                ->comment('Pedido concreto del consumidor.');

            $table->string('status', 20)->default('pending')
                ->comment('Estado del reclamo: pending, in_progress, resolved.');

            $table->timestamps();

            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
