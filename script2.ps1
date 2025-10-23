# Step 2: Generate All Models
Write-Host "Creating STU Key Management Eloquent Models..." -ForegroundColor Green

# 1. Create User model (extended for Spatie)
@'
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
'@ | Out-File -FilePath './app/Models/User.php' -Encoding UTF8 -Force

# 2. Create SecurityShift model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_at',
        'end_at',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function keyLogs()
    {
        return $this->hasMany(KeyLog::class, 'receiver_user_id', 'user_id')
            ->whereBetween('key_logs.created_at', [$this->start_at, $this->end_at ?? now()]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('end_at')
                    ->where('start_at', '<=', now());
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function endShift()
    {
        $this->update(['end_at' => now()]);
        return $this;
    }

    public function getDurationInMinutes()
    {
        if (!$this->end_at) {
            return now()->diffInMinutes($this->start_at);
        }

        return $this->end_at->diffInMinutes($this->start_at);
    }

    public function getCheckoutCount()
    {
        return $this->keyLogs()->where('action', 'checkout')->count();
    }
}
'@ | Out-File -FilePath './app/Models/SecurityShift.php' -Encoding UTF8 -Force

# 3. Create Location model
@'
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
'@ | Out-File -FilePath './app/Models/Location.php' -Encoding UTF8 -Force

# 4. Create Key model
# ... (rest of your code unchanged, only path fixed) ...

Write-Host "✅ First 8 models created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in app/Models/" -ForegroundColor Cyan
