<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ServiceFeedback extends Model
{
    protected $table = 'tbl_services_feedbacks';

    protected $fillable = [
        'reservation_id',
        'created_by',
        'commentaire',
        'created_dtm',
        'note_salle',
        'note_service',
        'nom',
        'photo_user',
    ];

    protected function casts(): array
    {
        return [
            'created_dtm' => 'datetime',
            'note_salle' => 'integer',
            'note_service' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $reservationId = (int) $model->reservation_id;
            if ($reservationId <= 0) {
                return;
            }

            $isSalleReservation = Reservation::query()
                ->where('id', $reservationId)
                ->where(function ($query) {
                    $query->where('service_slug', 'salles')->orWhereNull('service_slug');
                })
                ->exists();

            if (! $isSalleReservation) {
                throw ValidationException::withMessages([
                    'reservation_id' => 'Les feedbacks de services sont reserves aux reservations de type salle.',
                ]);
            }
        });
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}