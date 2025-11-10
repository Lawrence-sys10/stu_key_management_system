<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HolderTrait;

class HrStaff extends Model
{
    use HasFactory, SoftDeletes, HolderTrait;

    protected $table = 'hr_staff';

    protected $fillable = [
        'staff_id',
        'name',
        'phone',
        'dept',
        'email',
        'status',
        'synced_at',
        'source',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, $dept)
    {
        return $query->where('dept', $dept);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('staff_id', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function getRecentKeyLogs($limit = 5)
    {
        return $this->keyLogs()->with('key.location')->latest()->limit($limit)->get();
    }

    public function markAsSynced()
    {
        $this->update(['synced_at' => now()]);
        return $this;
    }
}
