<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('request_type', 20);
            $table->string('full_name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->string('service_slug', 100)->nullable();
            $table->date('event_date')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('message');
            $table->string('source_page', 150)->nullable();
            $table->string('status', 30)->default('new');
            $table->timestamps();

            $table->index(['request_type', 'status']);
            $table->index('service_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_inquiries');
    }
};