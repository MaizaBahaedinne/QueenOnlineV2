<?php

namespace App\Models;

use App\Models\Reservation;
use App\Models\SalleOption;
use Illuminate\Database\Eloquent\Model;

class ReservationSalleOption extends Model
{
    protected $fillable = [
        'reservation_id',
        'salle_option_id',
        'label',
        'amount',
        'note',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function option()
    {
        return $this->belongsTo(SalleOption::class, 'salle_option_id');
    }
}
