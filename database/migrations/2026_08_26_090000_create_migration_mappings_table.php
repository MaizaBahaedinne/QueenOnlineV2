<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 150);
            $table->string('source_column', 150);
            $table->string('target_table', 150);
            $table->string('target_column', 150);
            $table->string('condition_value', 255)->nullable();
            $table->text('signification')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['source_table', 'sort_order'], 'migration_map_source_sort_idx');
            $table->index(['target_table'], 'migration_map_target_table_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_mappings');
    }
};
