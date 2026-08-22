<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleFeature extends Model
{
    protected $fillable = [
        'module_id',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function permissions()
    {
        return $this->hasMany(RoleFeaturePermission::class);
    }
}
