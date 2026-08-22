<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salle extends Model
{
    protected $fillable = [
        'name',
        'salle_type',
        'capacity',
        'price_per_day',
        'status',
        'description',
        'location',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
