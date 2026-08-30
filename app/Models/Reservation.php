<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'client_id',
        'salle_id',
        'service_slug',
        'user_id',
        'title',
        'guest_count',
        'event_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'payment_due_date',
        'status',
        'total_amount',
        'note_admin',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function additionalServices()
    {
        return $this->hasMany(ReservationAdditionalService::class);
    }

    public function salleOptionRows()
    {
        return $this->hasMany(ReservationSalleOption::class);
    }

    public function serviceEntrees()
    {
        return $this->hasMany(ServiceEntree::class);
    }

    public function serviceFeedbacks()
    {
        return $this->hasMany(ServiceFeedback::class);
    }

    public function serviceAffectations()
    {
        return $this->hasMany(ServiceAffectation::class);
    }

    public function isSalleReservation(): bool
    {
        return ($this->service_slug ?? 'salles') === 'salles';
    }
}
