<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_services_entrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->unsignedInteger('quantite')->default(1);
            $table->string('nature');
            $table->string('moment_service')->nullable();
            $table->time('heure_prevu')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_dtm')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'moment_service'], 'services_entrees_reservation_moment_idx');
        });

        Schema::create('tbl_services_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->dateTime('created_dtm')->nullable();
            $table->unsignedTinyInteger('note_salle')->nullable();
            $table->unsignedTinyInteger('note_service')->nullable();
            $table->string('nom')->nullable();
            $table->string('photo_user')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'created_dtm'], 'services_feedbacks_reservation_created_idx');
        });

        Schema::create('tbl_service_affectation', function (Blueprint $table) {
            $table->id();
            $table->string('affectation')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->dateTime('created_dtm')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_chef')->default(false);
            $table->timestamps();

            $table->index(['reservation_id', 'user_id'], 'service_affectation_reservation_user_idx');
        });

        Schema::create('tbl_services_retours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entree_id')->constrained('tbl_services_entrees')->cascadeOnDelete();
            $table->unsignedInteger('quantite_retournee');
            $table->text('note_retour')->nullable();
            $table->dateTime('created_dtm')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entree_id', 'created_dtm'], 'services_retours_entree_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_services_retours');
        Schema::dropIfExists('tbl_service_affectation');
        Schema::dropIfExists('tbl_services_feedbacks');
        Schema::dropIfExists('tbl_services_entrees');
    }
};