<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shalom_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('department', 120);
            $table->string('province', 120);
            $table->string('district', 120);
            $table->string('address', 255);
            $table->string('modality', 30)->default('Regular');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('geocode_precision', 20)->nullable();
            $table->timestamp('geocoded_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['department', 'province', 'district']);
            $table->index(['latitude', 'longitude']);
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shalom_agencies');
    }
};
