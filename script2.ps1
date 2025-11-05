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
'@ | Out-File -FilePath .\app\Models\User.php -Encoding UTF8

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
'@ | Out-File -FilePath .\app\Models\SecurityShift.php -Encoding UTF8

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
'@ | Out-File -FilePath .\app\Models\Location.php -Encoding UTF8

# 4. Create Key model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Key extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'label',
        'description',
        'key_type',
        'location_id',
        'status',
        'last_log_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function keyTags()
    {
        return $this->hasMany(KeyTag::class);
    }

    public function keyLogs()
    {
        return $this->hasMany(KeyLog::class);
    }

    public function lastLog()
    {
        return $this->belongsTo(KeyLog::class, 'last_log_id');
    }

    public function currentHolder()
    {
        return $this->hasOne(KeyLog::class)
            ->where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->latest();
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('key_type', $type);
    }

    // Methods
    public function checkout($holderData, $receiverUserId, $expectedReturnAt = null)
    {
        $this->update(['status' => 'checked_out']);
        
        $log = KeyLog::create([
            'key_id' => $this->id,
            'action' => 'checkout',
            'holder_type' => $holderData['type'],
            'holder_id' => $holderData['id'],
            'holder_name' => $holderData['name'],
            'holder_phone' => $holderData['phone'],
            'receiver_user_id' => $receiverUserId,
            'receiver_name' => User::find($receiverUserId)->name,
            'expected_return_at' => $expectedReturnAt,
        ]);

        $this->update(['last_log_id' => $log->id]);

        return $log;
    }

    public function checkin($receiverUserId, $signaturePath = null, $photoPath = null, $notes = null)
    {
        $currentCheckout = $this->currentHolder;
        
        if (!$currentCheckout) {
            throw new \Exception('Key is not currently checked out');
        }

        $this->update(['status' => 'available']);

        $log = KeyLog::create([
            'key_id' => $this->id,
            'action' => 'checkin',
            'holder_type' => $currentCheckout->holder_type,
            'holder_id' => $currentCheckout->holder_id,
            'holder_name' => $currentCheckout->holder_name,
            'holder_phone' => $currentCheckout->holder_phone,
            'receiver_user_id' => $receiverUserId,
            'receiver_name' => User::find($receiverUserId)->name,
            'returned_from_log_id' => $currentCheckout->id,
            'signature_path' => $signaturePath,
            'photo_path' => $photoPath,
            'notes' => $notes,
        ]);

        $this->update(['last_log_id' => $log->id]);

        return $log;
    }

    public function isAvailable()
    {
        return $this->status === 'available';
    }

    public function isCheckedOut()
    {
        return $this->status === 'checked_out';
    }

    public function getCurrentHolderAttribute()
    {
        return $this->currentHolder;
    }

    public function getActiveKeyTag()
    {
        return $this->keyTags()->where('is_active', true)->first();
    }
}
'@ | Out-File -FilePath .\app\Models\Key.php -Encoding UTF8

# 5. Create KeyTag model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeyTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_id',
        'uuid',
        'printed_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function key()
    {
        return $this->belongsTo(Key::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrinted($query)
    {
        return $query->whereNotNull('printed_at');
    }

    public function scopeNotPrinted($query)
    {
        return $query->whereNull('printed_at');
    }

    // Methods
    public function markAsPrinted()
    {
        $this->update(['printed_at' => now()]);
        return $this;
    }

    public function getQrCodeUrlAttribute()
    {
        return route('kiosk.scan', ['uuid' => $this->uuid]);
    }

    public function getQrContentAttribute()
    {
        return "stu-keys://k/{$this->uuid}";
    }

    public function isPrinted()
    {
        return !is_null($this->printed_at);
    }
}
'@ | Out-File -FilePath .\app\Models\KeyTag.php -Encoding UTF8

# 6. Create HrStaff model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrStaff extends Model
{
    use HasFactory, SoftDeletes;

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

    // Relationships
    public function keyLogs()
    {
        return $this->morphMany(KeyLog::class, 'holder');
    }

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

    public function getCurrentHeldKeys()
    {
        return KeyLog::where('holder_type', 'hr')
                    ->where('holder_id', $this->id)
                    ->where('action', 'checkout')
                    ->whereNull('returned_from_log_id')
                    ->with('key.location')
                    ->get();
    }

    public function markAsSynced()
    {
        $this->update(['synced_at' => now()]);
        return $this;
    }
}
'@ | Out-File -FilePath .\app\Models\HrStaff.php -Encoding UTF8

# 7. Create PermanentStaffManual model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermanentStaffManual extends Model
{
    use HasFactory, SoftDeletes;

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

    public function keyLogs()
    {
        return $this->morphMany(KeyLog::class, 'holder');
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

    // Methods
    public function getCurrentHeldKeys()
    {
        return KeyLog::where('holder_type', 'perm_manual')
                    ->where('holder_id', $this->id)
                    ->where('action', 'checkout')
                    ->whereNull('returned_from_log_id')
                    ->with('key.location')
                    ->get();
    }
}
'@ | Out-File -FilePath .\app\Models\PermanentStaffManual.php -Encoding UTF8

# 8. Create TemporaryStaff model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemporaryStaff extends Model
{
    use HasFactory, SoftDeletes;

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

    // Relationships
    public function keyLogs()
    {
        return $this->morphMany(KeyLog::class, 'holder');
    }

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

    public function getCurrentHeldKeys()
    {
        return KeyLog::where('holder_type', 'temp')
                    ->where('holder_id', $this->id)
                    ->where('action', 'checkout')
                    ->whereNull('returned_from_log_id')
                    ->with('key.location')
                    ->get();
    }

    public function hasPhoto()
    {
        return !is_null($this->photo_path);
    }
}
'@ | Out-File -FilePath .\app\Models\TemporaryStaff.php -Encoding UTF8

Write-Host "✅ First 8 models created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in app/Models/" -ForegroundColor Cyan