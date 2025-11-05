<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManualStaff extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'department',
        'position',
        'staff_type',
        'is_active',
        'employee_id',
        'phone',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the key logs for this manual staff member.
     */
    public function keyLogs(): HasMany
    {
        return $this->hasMany(KeyLog::class, 'holder_id')
                    ->where('holder_type', 'manual_staff');
    }

    /**
     * Get the currently checked out keys.
     */
    public function currentKeys(): HasMany
    {
        return $this->keyLogs()->whereNull('returned_at');
    }

    /**
     * Scope active staff members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by staff type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('staff_type', $type);
    }

    /**
     * Get the display name for the staff member.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->employee_id ? "{$this->name} ({$this->employee_id})" : $this->name;
    }

    /**
     * Check if staff member has any keys checked out.
     */
    public function hasCheckedOutKeys(): bool
    {
        return $this->currentKeys()->exists();
    }
}