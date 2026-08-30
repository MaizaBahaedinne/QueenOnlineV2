<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_services_entree_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entree_id')->constrained('tbl_services_entrees')->cascadeOnDelete();
            $table->string('action', 40)->default('increment');
            $table->unsignedInteger('previous_quantite')->default(0);
            $table->unsignedInteger('delta_quantite')->default(0);
            $table->unsignedInteger('new_quantite')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_dtm')->nullable();
            $table->timestamps();

            $table->index(['entree_id', 'created_dtm'], 'services_entree_history_entree_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_services_entree_histories');
    }
};
