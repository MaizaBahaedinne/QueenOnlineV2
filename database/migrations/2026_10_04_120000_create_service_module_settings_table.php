<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug')->unique();
            $table->string('cover_image_path')->nullable();
            $table->timestamps();

            $table->index('module_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_module_settings');
    }
};
