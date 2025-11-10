<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function securityShifts()
    {
        return $this->hasMany(SecurityShift::class);
    }

    public function keyLogsAsReceiver()
    {
        return $this->hasMany(KeyLog::class, 'receiver_user_id');
    }

    public function permanentStaffManualEntries()
    {
        return $this->hasMany(PermanentStaffManual::class, 'added_by');
    }

    public function getCurrentShiftAttribute()
    {
        return $this->securityShifts()
            ->whereNull('end_at')
            ->where('start_at', '<=', now())
            ->first();
    }

    public function isOnShift()
    {
        return !is_null($this->current_shift);
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : asset('images/default-avatar.png');
    }
}
