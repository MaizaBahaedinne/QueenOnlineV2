<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceEntreeHistory extends Model
{
    protected $table = 'tbl_services_entree_histories';

    protected $fillable = [
        'entree_id',
        'action',
        'previous_quantite',
        'delta_quantite',
        'new_quantite',
        'note',
        'created_by',
        'created_dtm',
    ];

    protected function casts(): array
    {
        return [
            'previous_quantite' => 'integer',
            'delta_quantite' => 'integer',
            'new_quantite' => 'integer',
            'created_dtm' => 'datetime',
        ];
    }

    public function entree()
    {
        return $this->belongsTo(ServiceEntree::class, 'entree_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
