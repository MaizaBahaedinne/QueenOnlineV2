<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('singer_troupe_partnership_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('singer_item_id')->constrained('service_module_items')->cascadeOnDelete();
            $table->foreignId('troupe_item_id')->constrained('service_module_items')->cascadeOnDelete();
            $table->decimal('partnership_price', 12, 2);
            $table->timestamps();

            $table->unique(['singer_item_id', 'troupe_item_id'], 'singer_troupe_unique');
            $table->index(['troupe_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('singer_troupe_partnership_prices');
    }
};