<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('service_slug')->nullable()->after('salle_id');
            $table->index('service_slug');
        });

        // Preserve expected behavior for legacy reservations.
        DB::table('reservations')->whereNull('service_slug')->update(['service_slug' => 'salles']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['service_slug']);
            $table->dropColumn('service_slug');
        });
    }
};
