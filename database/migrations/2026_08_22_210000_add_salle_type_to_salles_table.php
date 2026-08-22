<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salles', function (Blueprint $table) {
            $table->string('salle_type')->default('couvert')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('salles', function (Blueprint $table) {
            $table->dropColumn('salle_type');
        });
    }
};
