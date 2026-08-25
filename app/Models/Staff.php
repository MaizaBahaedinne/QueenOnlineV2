<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @mixin \App\Models\StaffDocument */

class Staff extends Model
{
    protected $fillable = [
        'photo_path',
        'first_name',
        'last_name',
        'date_of_birth',
        'birth_place',
        'nationality',
        'cin',
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
        'hire_date',
        'position_title',
        'department_id',
        'employment_type',
        'contract_type',
        'contract_start_date',
        'contract_end_date',
        'probation_period',
        'work_location',
        'work_schedule',
        'manager_id',
        'user_id',
        'status',
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
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'cin_issued_at' => 'date',
        'hire_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'exit_date' => 'date',
        'base_salary' => 'decimal:3',
        'fixed_bonus' => 'decimal:3',
        'variable_bonus' => 'decimal:3',
        'allowances' => 'decimal:3',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(StaffDocument::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        return trim((string) ($this->first_name . ' ' . $this->last_name));
    }
}
