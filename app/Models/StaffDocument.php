<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDocument extends Model
{
    protected $fillable = [
        'staff_id',
        'document_type',
        'document_label',
        'original_name',
        'file_path',
        'uploaded_by',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}