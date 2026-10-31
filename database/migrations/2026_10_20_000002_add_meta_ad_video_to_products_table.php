<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_ad_video_url', 2048)
                ->nullable()
                ->after('video_public_id');

            $table->string('meta_ad_video_public_id')
                ->nullable()
                ->after('meta_ad_video_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'meta_ad_video_url',
                'meta_ad_video_public_id',
            ]);
        });
    }
};
