<?php

namespace App\Models;

use App\Traits\HolderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrStaff extends Model
{
    use HolderTrait;

    protected $fillable = [
        'staff_id',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
