<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_meta_ads', function (Blueprint $table): void {
            $table->unsignedBigInteger('link_clicks_30d')
                ->default(0)
                ->after('review_status');
            $table->timestamp('metrics_synced_at')
                ->nullable()
                ->after('link_clicks_30d');
        });
    }

    public function down(): void
    {
        Schema::table('product_meta_ads', function (Blueprint $table): void {
            $table->dropColumn(['link_clicks_30d', 'metrics_synced_at']);
        });
    }
};
