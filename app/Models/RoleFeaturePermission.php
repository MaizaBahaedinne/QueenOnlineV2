<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleFeaturePermission extends Model
{
    protected $fillable = [
        'role_id',
        'module_feature_id',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function feature()
    {
        return $this->belongsTo(ModuleFeature::class, 'module_feature_id');
    }
}
