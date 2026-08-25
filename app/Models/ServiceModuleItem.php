<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceModuleItem extends Model
{
    protected $fillable = [
        'module_slug',
        'name',
        'phone',
        'base_price',
        'status',
        'notes',
    ];

    public function packs()
    {
        return $this->hasMany(ServiceModulePack::class);
    }

    public function singerPartnershipPrices()
    {
        return $this->hasMany(SingerTroupePartnershipPrice::class, 'singer_item_id');
    }

    public function troupePartnershipPrices()
    {
        return $this->hasMany(SingerTroupePartnershipPrice::class, 'troupe_item_id');
    }
}
