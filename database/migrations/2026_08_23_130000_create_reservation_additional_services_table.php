<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_additional_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('module_slug');
            $table->foreignId('service_module_item_id')->nullable()->constrained('service_module_items')->nullOnDelete();
            $table->foreignId('service_module_pack_id')->nullable()->constrained('service_module_packs')->nullOnDelete();
            $table->string('label');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'module_slug'], 'ras_reservation_module_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_additional_services');
    }
};
