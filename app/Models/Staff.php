<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable = [
        'photo_path',
        'first_name',
        'last_name',
        'cin',
        'hire_date',
        'position_title',
        'department_id',
        'employment_type',
        'contract_type',
        'manager_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'hire_date' => 'date',
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

    public function getFullNameAttribute(): string
    {
        return trim((string) ($this->first_name . ' ' . $this->last_name));
    }
}
