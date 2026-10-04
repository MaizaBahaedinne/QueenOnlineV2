<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceModuleSetting extends Model
{
    protected $fillable = [
        'module_slug',
        'cover_image_path',
    ];
}
