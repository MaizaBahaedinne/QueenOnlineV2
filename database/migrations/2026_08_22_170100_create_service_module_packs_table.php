<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_module_packs', function (Blueprint $table) {
            $table->id();
            $table->string('module_slug');
            $table->foreignId('service_module_item_id')->nullable()->constrained('service_module_items')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('module_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_module_packs');
    }
};
