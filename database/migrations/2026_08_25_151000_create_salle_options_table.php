<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salle_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salle_id')->constrained('salles')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['salle_id', 'status'], 'salle_options_salle_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salle_options');
    }
};
