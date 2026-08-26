<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationMapping extends Model
{
    protected $fillable = [
        'source_table',
        'source_column',
        'target_table',
        'target_column',
        'condition_value',
        'signification',
        'sort_order',
        'is_active',
    ];
}
