<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('present_on_site')->default(false);
            $table->boolean('termination_signed')->default(false);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_cancellations');
    }
};
