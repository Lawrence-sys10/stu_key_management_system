<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HolderTrait;

class TemporaryStaff extends Model
{
    use HasFactory, SoftDeletes, HolderTrait;

    protected $fillable = [
        'name',
        'phone',
        'id_number',
        'photo_path',
        'dept',
        'notes',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('id_number', 'like', "%{$search}%");
        });
    }

    // Methods
    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function hasPhoto()
    {
        return !is_null($this->photo_path);
    }
}
