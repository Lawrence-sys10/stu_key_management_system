<?php

namespace App\Models;

use App\Traits\HolderTrait;
use Illuminate\Database\Eloquent\Model;

class PermanentStaffManual extends Model
{
    use HolderTrait;

    protected $fillable = [
        'name',
        'identification_number',
        'phone',
        'email',
        'department',
        'position',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
