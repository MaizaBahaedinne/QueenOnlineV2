<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_salle_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('salle_option_id')->nullable()->constrained('salle_options')->nullOnDelete();
            $table->string('label');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['reservation_id'], 'reservation_salle_options_reservation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_salle_options');
    }
};
