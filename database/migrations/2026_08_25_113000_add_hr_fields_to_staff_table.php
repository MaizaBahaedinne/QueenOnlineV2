<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('birth_place')->nullable()->after('date_of_birth');
            $table->string('nationality')->nullable()->after('birth_place');
            $table->date('cin_issued_at')->nullable()->after('cin');
            $table->text('address_line')->nullable()->after('cin_issued_at');
            $table->string('governorate')->nullable()->after('address_line');
            $table->string('delegation')->nullable()->after('governorate');
            $table->string('phone')->nullable()->after('delegation');
            $table->string('email')->nullable()->after('phone');
            $table->string('marital_status')->nullable()->after('email');
            $table->unsignedInteger('dependent_children_count')->nullable()->after('marital_status');
            $table->string('emergency_contact_name')->nullable()->after('dependent_children_count');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_relationship');
            $table->string('emergency_contact_phone_secondary')->nullable()->after('emergency_contact_phone');
            $table->string('employee_code')->nullable()->unique()->after('emergency_contact_phone_secondary');
            $table->date('contract_start_date')->nullable()->after('contract_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->string('probation_period')->nullable()->after('contract_end_date');
            $table->string('work_location')->nullable()->after('probation_period');
            $table->string('work_schedule')->nullable()->after('work_location');
            $table->date('exit_date')->nullable()->after('status');
            $table->text('exit_reason')->nullable()->after('exit_date');
            $table->decimal('base_salary', 12, 3)->nullable()->after('exit_reason');
            $table->decimal('fixed_bonus', 12, 3)->nullable()->after('base_salary');
            $table->decimal('variable_bonus', 12, 3)->nullable()->after('fixed_bonus');
            $table->decimal('allowances', 12, 3)->nullable()->after('variable_bonus');
            $table->string('payment_method')->nullable()->after('allowances');
            $table->string('bank_name')->nullable()->after('payment_method');
            $table->string('rib')->nullable()->after('bank_name');
            $table->string('cnss_number')->nullable()->after('rib');
            $table->string('cnss_affiliation_number')->nullable()->after('cnss_number');
            $table->text('tax_information')->nullable()->after('cnss_affiliation_number');
        });

        DB::table('staff')->where('status', 'inactive')->update(['status' => 'suspended']);
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropUnique('staff_employee_code_unique');
            $table->dropColumn([
                'date_of_birth',
                'birth_place',
                'nationality',
                'cin_issued_at',
                'address_line',
                'governorate',
                'delegation',
                'phone',
                'email',
                'marital_status',
                'dependent_children_count',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'emergency_contact_phone_secondary',
                'employee_code',
                'contract_start_date',
                'contract_end_date',
                'probation_period',
                'work_location',
                'work_schedule',
                'exit_date',
                'exit_reason',
                'base_salary',
                'fixed_bonus',
                'variable_bonus',
                'allowances',
                'payment_method',
                'bank_name',
                'rib',
                'cnss_number',
                'cnss_affiliation_number',
                'tax_information',
            ]);
        });
    }
};