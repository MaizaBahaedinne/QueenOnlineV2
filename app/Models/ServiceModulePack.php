<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceModulePack extends Model
{
    protected $fillable = [
        'module_slug',
        'service_module_item_id',
        'name',
        'price',
        'status',
        'description',
    ];

    public function item()
    {
        return $this->belongsTo(ServiceModuleItem::class, 'service_module_item_id');
    }
}
