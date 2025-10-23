<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'campus',
        'building',
        'room',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function keys()
    {
        return $this->hasMany(Key::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCampus($query, $campus)
    {
        return $query->where('campus', $campus);
    }

    public function scopeByBuilding($query, $building)
    {
        return $query->where('building', $building);
    }

    // Methods
    public function getFullAddressAttribute()
    {
        $address = "{$this->campus} - {$this->building}";
        if ($this->room) {
            $address .= " - Room {$this->room}";
        }
        return $address;
    }

    public function getAvailableKeysCount()
    {
        return $this->keys()->where('status', 'available')->count();
    }

    public function getCheckedOutKeysCount()
    {
        return $this->keys()->where('status', 'checked_out')->count();
    }
}
