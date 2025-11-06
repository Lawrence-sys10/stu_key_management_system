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
        return $this->morphTo('holder', 'holder_type', 'holder_id')
            ->withDefault(function () {
                return $this->getDefaultHolder();
            });
    }

    /**
     * Get a default holder object with the stored data
     */
    protected function getDefaultHolder()
    {
        return new class($this) {
            protected $keyLog;

            public function __construct(KeyLog $keyLog)
            {
                $this->keyLog = $keyLog;
            }

            public function __get($property)
            {
                // Return stored holder data when accessed
                if ($property === 'name') {
                    return $this->keyLog->holder_name ?? 'Unknown Holder';
                }
                if ($property === 'phone') {
                    return $this->keyLog->holder_phone ?? 'N/A';
                }
                return null;
            }

            public function exists()
            {
                return false;
            }
        };
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

    /**
     * Safe method to get holder name without triggering morph relationship errors
     */
    public function getHolderDisplayNameAttribute()
    {
        return $this->holder_name ?? 'Unknown Holder';
    }

    /**
     * Safe method to get holder phone without triggering morph relationship errors
     */
    public function getHolderDisplayPhoneAttribute()
    {
        return $this->holder_phone ?? 'N/A';
    }
}