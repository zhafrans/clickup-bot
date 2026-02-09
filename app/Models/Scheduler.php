<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scheduler extends Model
{
    protected $fillable = [
        'name',
        'run_time',
        'days_of_week',
        'last_run',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'last_run' => 'datetime',
        'is_active' => 'boolean',
    ];
}
