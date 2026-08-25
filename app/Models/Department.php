<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function staffMembers()
    {
        return $this->hasMany(Staff::class);
    }
}
