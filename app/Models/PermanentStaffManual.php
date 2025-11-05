<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HolderTrait;

class PermanentStaffManual extends Model
{
    use HasFactory, SoftDeletes, HolderTrait;

    protected $table = 'permanent_staff_manual';

    protected $fillable = [
        'staff_id',
        'name',
        'phone',
        'dept',
        'added_by',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('staff_id', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
