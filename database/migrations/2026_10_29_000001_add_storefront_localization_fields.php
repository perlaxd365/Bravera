<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
        });

        Schema::table('course_assets', function (Blueprint $table): void {
            $table->string('locale', 8)->default('es')->index();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('display_currency', 3)->nullable();
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->decimal('display_total', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['display_currency', 'exchange_rate', 'display_total']);
        });

        Schema::table('course_assets', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });

        Schema::dropIfExists('product_translations');
    }
};
