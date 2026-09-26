<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('active')
                ->after('quantity')
                ->index()
                ->comment('active | cancelled | refunded');

            $table->dateTime('cancelled_at')
                ->nullable()
                ->after('status');

            $table->unsignedBigInteger('cancelled_by')
                ->nullable()
                ->after('cancelled_at')
                ->comment('Usuario del panel que canceló la línea.');

            $table->text('cancellation_reason')
                ->nullable()
                ->after('cancelled_by');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('cancelled_at')
                ->nullable()
                ->after('paid_at')
                ->comment('Fecha de cancelación total del pedido.');

            $table->unsignedBigInteger('cancelled_by')
                ->nullable()
                ->after('cancelled_at');

            $table->text('cancellation_reason')
                ->nullable()
                ->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });
    }
};
