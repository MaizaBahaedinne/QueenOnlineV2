<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationAdditionalService extends Model
{
    protected $fillable = [
        'reservation_id',
        'module_slug',
        'service_module_item_id',
        'service_module_pack_id',
        'label',
        'amount',
        'note',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function item()
    {
        return $this->belongsTo(ServiceModuleItem::class, 'service_module_item_id');
    }

    public function pack()
    {
        return $this->belongsTo(ServiceModulePack::class, 'service_module_pack_id');
    }
}
