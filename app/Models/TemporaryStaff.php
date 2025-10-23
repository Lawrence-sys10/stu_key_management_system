<?php

namespace App\Models;

use App\Traits\HolderTrait;
use Illuminate\Database\Eloquent\Model;

class TemporaryStaff extends Model
{
    use HolderTrait;

    protected $fillable = [
        'name',
        'identification_number',
        'phone',
        'company',
        'purpose',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
