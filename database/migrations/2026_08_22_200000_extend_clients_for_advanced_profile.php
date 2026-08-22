<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_type')->default('personne-physique')->after('user_id');
            $table->string('fiscal_number')->nullable()->after('client_type');
            $table->string('company_name')->nullable()->after('fiscal_number');
            $table->string('first_name')->nullable()->after('company_name');
            $table->string('gender')->nullable()->after('name');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('address_number')->nullable()->after('cin');
            $table->string('address_street')->nullable()->after('address_number');
            $table->string('governorate')->nullable()->after('city');
            $table->string('phone_label_1')->nullable()->after('phone');
            $table->string('phone_2')->nullable()->after('phone_label_1');
            $table->string('phone_label_2')->nullable()->after('phone_2');
            $table->string('source')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'client_type',
                'fiscal_number',
                'company_name',
                'first_name',
                'gender',
                'birth_date',
                'address_number',
                'address_street',
                'governorate',
                'phone_label_1',
                'phone_2',
                'phone_label_2',
                'source',
            ]);
        });
    }
};
