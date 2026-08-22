<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_module_items', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('module_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_module_items');
    }
};
