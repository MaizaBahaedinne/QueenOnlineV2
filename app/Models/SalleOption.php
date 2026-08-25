<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalleOption extends Model
{
    protected $fillable = [
        'salle_id',
        'name',
        'price',
        'status',
        'note',
    ];

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function reservationRows()
    {
        return $this->hasMany(ReservationSalleOption::class);
    }
}
