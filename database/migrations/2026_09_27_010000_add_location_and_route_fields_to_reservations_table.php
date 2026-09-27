<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('address_number')->nullable()->after('note_admin');
            $table->string('address_street')->nullable()->after('address_number');
            $table->string('city')->nullable()->after('address_street');
            $table->string('governorate')->nullable()->after('city');
            $table->decimal('latitude', 10, 7)->nullable()->after('governorate');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('itinerary_departure')->nullable()->after('longitude');
            $table->text('itinerary_stops')->nullable()->after('itinerary_departure');
            $table->string('itinerary_return')->nullable()->after('itinerary_stops');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'address_number',
                'address_street',
                'city',
                'governorate',
                'latitude',
                'longitude',
                'itinerary_departure',
                'itinerary_stops',
                'itinerary_return',
            ]);
        });
    }
};