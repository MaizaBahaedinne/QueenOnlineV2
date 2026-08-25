<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_credit_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 12, 2);
            $table->foreignId('related_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'type']);
            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_credit_ledgers');
    }
};
