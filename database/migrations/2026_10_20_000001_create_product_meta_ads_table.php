<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_meta_ads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ad_account_id', 64);
            $table->string('campaign_id', 64);
            $table->string('campaign_name')->nullable();
            $table->string('ad_set_id', 64);
            $table->string('ad_set_name')->nullable();

            $table->string('ad_id', 64)->nullable()->index();
            $table->string('creative_id', 64)->nullable()->index();

            $table->string('media_type', 16);
            $table->string('status', 32)->default('processing')->index();
            $table->string('review_status', 64)->nullable();
            $table->text('error_message')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Un producto se publica una vez por conjunto de anuncios. Un
            // reintento actualiza el mismo registro en lugar de duplicar el ad.
            $table->unique(['product_id', 'ad_set_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_meta_ads');
    }
};
