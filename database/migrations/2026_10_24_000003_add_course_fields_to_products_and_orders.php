<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('product_type', 20)
                ->default('physical')
                ->after('brand_id')
                ->index();
            $table->decimal('course_price', 10, 2)
                ->nullable()
                ->after('product_type');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('completed_at')
                ->nullable()
                ->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('completed_at');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['product_type', 'course_price']);
        });
    }
};
