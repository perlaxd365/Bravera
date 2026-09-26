<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('order_id')
                ->nullable()
                ->comment('Pedido donde se aplicó. La FK se agrega en una migración posterior.');

            $table->decimal('subtotal', 10, 2)
                ->comment('Subtotal sobre el que se aplicó.');

            $table->decimal('discount_amount', 10, 2)
                ->comment('Descuento efectivamente aplicado.');

            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
