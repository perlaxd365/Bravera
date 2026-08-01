<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Identificación
            |--------------------------------------------------------------------------
            */

            $table->id()
                ->comment('Identificador único del proveedor.');

            $table->string('code', 20)
                ->unique()
                ->comment('Código único del proveedor. Ejemplo: SUP000001.');

            $table->string('business_name', 200)
                ->comment('Razón social.');

            $table->string('trade_name', 200)
                ->nullable()
                ->comment('Nombre comercial.');

            $table->string('tax_id', 20)
                ->nullable()
                ->comment('RUC del proveedor.');

            /*
            |--------------------------------------------------------------------------
            | Contacto
            |--------------------------------------------------------------------------
            */

            $table->string('contact_name', 150)
                ->nullable()
                ->comment('Persona de contacto.');

            $table->string('email', 150)
                ->nullable()
                ->comment('Correo electrónico.');

            $table->string('phone', 30)
                ->nullable()
                ->comment('Teléfono.');

            $table->string('whatsapp', 30)
                ->nullable()
                ->comment('Número de WhatsApp.');

            $table->string('website')
                ->nullable()
                ->comment('Sitio web.');

            /*
            |--------------------------------------------------------------------------
            | Dirección
            |--------------------------------------------------------------------------
            */

            $table->string('department', 100)
                ->nullable()
                ->comment('Departamento.');

            $table->string('province', 100)
                ->nullable()
                ->comment('Provincia.');

            $table->string('district', 100)
                ->nullable()
                ->comment('Distrito.');

            $table->string('address')
                ->nullable()
                ->comment('Dirección.');

            /*
            |--------------------------------------------------------------------------
            | Operación
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('estimated_dispatch_days')
                ->default(1)
                ->comment('Tiempo promedio de despacho en días.');

            $table->enum('status', ['active', 'inactive'])
                ->default('active')
                ->comment('Indica si el proveedor está activo.');

            $table->text('internal_notes')
                ->nullable()
                ->comment('Observaciones internas del proveedor.');

            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que creó el registro.');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Último usuario que modificó el registro.');

            $table->timestamps();

            $table->softDeletes()
                ->comment('Fecha de eliminación lógica.');

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('business_name');
            $table->index('trade_name');
            $table->index('tax_id');
            $table->index('status');
            $table->index(['department', 'province', 'district']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
