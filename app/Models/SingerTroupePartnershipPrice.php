<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SingerTroupePartnershipPrice extends Model
{
    protected $fillable = [
        'singer_item_id',
        'troupe_item_id',
        'partnership_price',
    ];

    public function singer()
    {
        return $this->belongsTo(ServiceModuleItem::class, 'singer_item_id');
    }

    public function troupe()
    {
        return $this->belongsTo(ServiceModuleItem::class, 'troupe_item_id');
    }
}