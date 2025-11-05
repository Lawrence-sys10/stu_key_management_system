# Step 3: Generate Remaining Models
Write-Host "Creating Remaining STU Key Management Models..." -ForegroundColor Green

# 9. Create KeyLog model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_id',
        'action',
        'holder_type',
        'holder_id',
        'holder_name',
        'holder_phone',
        'receiver_user_id',
        'receiver_name',
        'expected_return_at',
        'returned_from_log_id',
        'signature_path',
        'photo_path',
        'notes',
        'verified',
        'discrepancy',
        'discrepancy_reason',
    ];

    protected $casts = [
        'expected_return_at' => 'datetime',
        'verified' => 'boolean',
        'discrepancy' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function key()
    {
        return $this->belongsTo(Key::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function returnedFromLog()
    {
        return $this->belongsTo(KeyLog::class, 'returned_from_log_id');
    }

    public function checkoutLog()
    {
        return $this->hasOne(KeyLog::class, 'returned_from_log_id');
    }

    public function holder()
    {
        return $this->morphTo();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Scopes
    public function scopeCheckout($query)
    {
        return $query->where('action', 'checkout');
    }

    public function scopeCheckin($query)
    {
        return $query->where('action', 'checkin');
    }

    public function scopeOpenCheckouts($query)
    {
        return $query->where('action', 'checkout')
                    ->whereNull('returned_from_log_id');
    }

    public function scopeWithDiscrepancy($query)
    {
        return $query->where('discrepancy', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('verified', false);
    }

    public function scopeOverdue($query)
    {
        return $query->where('action', 'checkout')
                    ->whereNull('returned_from_log_id')
                    ->where('expected_return_at', '<', now());
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeForReceiver($query, $userId)
    {
        return $query->where('receiver_user_id', $userId);
    }

    // Methods
    public function isCheckout()
    {
        return $this->action === 'checkout';
    }

    public function isCheckin()
    {
        return $this->action === 'checkin';
    }

    public function isOpenCheckout()
    {
        return $this->isCheckout() && is_null($this->returned_from_log_id);
    }

    public function isOverdue()
    {
        return $this->isOpenCheckout() && 
               $this->expected_return_at && 
               $this->expected_return_at->lt(now());
    }

    public function getDurationInMinutes()
    {
        if ($this->isOpenCheckout()) {
            return now()->diffInMinutes($this->created_at);
        }

        if ($this->isCheckin() && $this->returnedFromLog) {
            return $this->created_at->diffInMinutes($this->returnedFromLog->created_at);
        }

        return null;
    }

    public function getSignatureUrlAttribute()
    {
        return $this->signature_path ? asset('storage/' . $this->signature_path) : null;
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function markAsVerified()
    {
        $this->update([
            'verified' => true,
            'discrepancy' => false,
            'discrepancy_reason' => null,
        ]);
        return $this;
    }

    public function markWithDiscrepancy($reason)
    {
        $this->update([
            'verified' => false,
            'discrepancy' => true,
            'discrepancy_reason' => $reason,
        ]);
        return $this;
    }

    public function getHolderTypeLabelAttribute()
    {
        return match($this->holder_type) {
            'hr' => 'HR Staff',
            'perm_manual' => 'Permanent Staff (Manual)',
            'temp' => 'Temporary Staff',
            default => 'Unknown',
        };
    }
}
'@ | Out-File -FilePath .\app\Models\KeyLog.php -Encoding UTF8

# 10. Create Notification model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_log_id',
        'channel',
        'to',
        'template_key',
        'payload_json',
        'status',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'sent_at' => 'datetime',
    ];

    // Relationships
    public function keyLog()
    {
        return $this->belongsTo(KeyLog::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSms($query)
    {
        return $query->where('channel', 'sms');
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('channel', 'whatsapp');
    }

    public function scopeEmail($query)
    {
        return $query->where('channel', 'email');
    }

    public function scopeForTemplate($query, $templateKey)
    {
        return $query->where('template_key', $templateKey);
    }

    // Methods
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error' => null,
        ]);
        return $this;
    }

    public function markAsFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
        ]);
        return $this;
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function getPayloadValue($key, $default = null)
    {
        return $this->payload_json[$key] ?? $default;
    }

    public function getChannelLabelAttribute()
    {
        return match($this->channel) {
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            default => ucfirst($this->channel),
        };
    }
}
'@ | Out-File -FilePath .\app\Models\Notification.php -Encoding UTF8

# 11. Create AnalyticsCache model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsCache extends Model
{
    use HasFactory;

    protected $table = 'analytics_cache';

    protected $fillable = [
        'date',
        'hour_bucket',
        'total_checkouts',
        'avg_duration_minutes',
        'busiest_flag',
    ];

    protected $casts = [
        'date' => 'date',
        'busiest_flag' => 'boolean',
    ];

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeBusiestHours($query)
    {
        return $query->where('busiest_flag', true);
    }

    public function scopeByHour($query, $hour)
    {
        return $query->where('hour_bucket', $hour);
    }

    // Methods
    public function getTimeRangeAttribute()
    {
        $startHour = str_pad($this->hour_bucket, 2, '0', STR_PAD_LEFT);
        $endHour = str_pad(($this->hour_bucket + 1) % 24, 2, '0', STR_PAD_LEFT);
        return "{$startHour}:00 - {$endHour}:00";
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('M j, Y');
    }

    public static function updateOrCreateMetrics($date, $hour, $checkouts, $avgDuration, $isBusiest = false)
    {
        return static::updateOrCreate(
            [
                'date' => $date,
                'hour_bucket' => $hour,
            ],
            [
                'total_checkouts' => $checkouts,
                'avg_duration_minutes' => $avgDuration,
                'busiest_flag' => $isBusiest,
            ]
        );
    }

    public static function getBusiestHourForDate($date)
    {
        return static::forDate($date)
            ->orderBy('total_checkouts', 'desc')
            ->first();
    }
}
'@ | Out-File -FilePath .\app\Models\AnalyticsCache.php -Encoding UTF8

# 12. Create Setting model
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'string', // Will be cast based on type
    ];

    // Scopes
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeGeneral($query)
    {
        return $query->where('group', 'general');
    }

    public function scopeNotifications($query)
    {
        return $query->where('group', 'notifications');
    }

    public function scopeHr($query)
    {
        return $query->where('group', 'hr');
    }

    public function scopePwa($query)
    {
        return $query->where('group', 'pwa');
    }

    // Methods
    public function getCastValue()
    {
        return match($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }

    public function setValueAttribute($value)
    {
        if ($this->type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($this->type === 'boolean') {
            $value = $value ? 'true' : 'false';
        }

        $this->attributes['value'] = (string) $value;
    }

    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->getCastValue() : $default;
    }

    public static function setValue($key, $value)
    {
        $setting = static::firstOrNew(['key' => $key]);
        
        if (!$setting->exists) {
            // Set default type based on value if new
            $setting->type = gettype($value);
            $setting->group = 'general';
        }

        $setting->value = $value;
        $setting->save();

        return $setting;
    }

    public static function getGroupSettings($group)
    {
        return static::group($group)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getCastValue()];
        })->toArray();
    }
}
'@ | Out-File -FilePath .\app\Models\Setting.php -Encoding UTF8

# 13. Create Holder Trait for polymorphic relationships
@'
<?php

namespace App\Traits;

trait HolderTrait
{
    /**
     * Get all key logs for this holder
     */
    public function keyLogs()
    {
        return $this->morphMany(\App\Models\KeyLog::class, 'holder');
    }

    /**
     * Get currently held keys
     */
    public function getCurrentHeldKeys()
    {
        return \App\Models\KeyLog::where('holder_type', $this->getMorphClass())
            ->where('holder_id', $this->id)
            ->where('action', 'checkout')
            ->whereNull('returned_from_log_id')
            ->with('key.location')
            ->get();
    }

    /**
     * Check if holder currently has any keys
     */
    public function hasHeldKeys()
    {
        return $this->getCurrentHeldKeys()->count() > 0;
    }

    /**
     * Get key checkout history
     */
    public function getKeyHistory($limit = null)
    {
        $query = $this->keyLogs()->with('key.location')->latest();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Get holder display name
     */
    public function getHolderDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->phone . ')';
    }

    /**
     * Get holder type label
     */
    public function getHolderTypeLabelAttribute()
    {
        return match($this->getMorphClass()) {
            'App\Models\HrStaff' => 'HR Staff',
            'App\Models\PermanentStaffManual' => 'Permanent Staff (Manual)',
            'App\Models\TemporaryStaff' => 'Temporary Staff',
            default => 'Unknown',
        };
    }
}
'@ | Out-File -FilePath .\app\Traits\HolderTrait.php -Encoding UTF8

# 14. Update HrStaff model to use HolderTrait
@'
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
'@ | Out-File -FilePath .\app\Models\HrStaff.php -Encoding UTF8 -Force

# 15. Update PermanentStaffManual model to use HolderTrait
@'
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
'@ | Out-File -FilePath .\app\Models\PermanentStaffManual.php -Encoding UTF8 -Force

# 16. Update TemporaryStaff model to use HolderTrait
@'
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
'@ | Out-File -FilePath .\app\Models\TemporaryStaff.php -Encoding UTF8 -Force

Write-Host "✅ All 15 models created successfully!" -ForegroundColor Green
Write-Host "📁 Files created in app/Models/ and app/Traits/" -ForegroundColor Cyan
Write-Host "➡️ Models include: KeyLog, Notification, AnalyticsCache, Setting, and HolderTrait" -ForegroundColor Yellow