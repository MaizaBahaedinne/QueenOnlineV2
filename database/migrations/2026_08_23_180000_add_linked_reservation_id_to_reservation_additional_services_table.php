<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_additional_services', function (Blueprint $table) {
            $table->foreignId('linked_reservation_id')
                ->nullable()
                ->after('reservation_id')
                ->constrained('reservations')
                ->nullOnDelete();

            $table->index('linked_reservation_id', 'ras_linked_reservation_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_additional_services', function (Blueprint $table) {
            $table->dropIndex('ras_linked_reservation_idx');
            $table->dropConstrainedForeignId('linked_reservation_id');
        });
    }
};
