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
        'returned_by_type',     // NEW
        'returned_by_id',       // NEW
        'returned_by_name',     // NEW
        'returned_by_phone',    // NEW
        'actual_return_at',     // NEW
        'signature_path',
        'photo_path',
        'notes',
        'verified',
        'discrepancy',
        'discrepancy_reason',
    ];

    protected $casts = [
        'expected_return_at' => 'datetime',
        'actual_return_at' => 'datetime',  // NEW
        'verified' => 'boolean',
        'discrepancy' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'signature_url',
        'photo_url',
        'holder_display_name',
        'holder_display_phone',
        'holder_type_label',
        'returned_by_display_name',  // NEW
        'returned_by_display_phone', // NEW
        'is_returned_by_different_person', // NEW
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
        // Only attempt morph relationship if holder_type and holder_id are set
        if ($this->holder_type && $this->holder_id) {
            return $this->morphTo('holder', 'holder_type', 'holder_id')
                ->withDefault(function () {
                    return $this->getDefaultHolder();
                });
        }
        
        // Return default holder if morph relationship is not available
        return $this->getDefaultHolder();
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
                if ($property === 'email') {
                    return $this->keyLog->holder_email ?? 'N/A';
                }
                return null;
            }

            public function exists()
            {
                return false;
            }
            
            public function getAttribute($key)
            {
                return $this->__get($key);
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
                    ->whereNotNull('expected_return_at')
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

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // NEW: Scope for returns by different persons
    public function scopeReturnedByDifferentPerson($query)
    {
        return $query->where('action', 'checkin')
                    ->where(function($q) {
                        $q->whereColumn('holder_name', '!=', 'returned_by_name')
                         ->orWhereColumn('holder_phone', '!=', 'returned_by_phone');
                    });
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

    // NEW: Check if returned by different person
    public function isReturnedByDifferentPerson()
    {
        if (!$this->isCheckin()) return false;
        
        return $this->holder_name !== $this->returned_by_name || 
               $this->holder_phone !== $this->returned_by_phone;
    }

    // NEW: Get the person who actually returned the key
    public function getReturnedByPersonAttribute()
    {
        if ($this->returned_by_name) {
            return [
                'name' => $this->returned_by_name,
                'phone' => $this->returned_by_phone,
                'type' => $this->returned_by_type,
                'is_different' => $this->isReturnedByDifferentPerson(),
            ];
        }

        // Fallback to original holder if no specific returner recorded
        return [
            'name' => $this->holder_name,
            'phone' => $this->holder_phone,
            'type' => $this->holder_type,
            'is_different' => false,
        ];
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

    public function getDurationInHours()
    {
        $minutes = $this->getDurationInMinutes();
        return $minutes ? round($minutes / 60, 2) : null;
    }

    public function getSignatureUrlAttribute()
    {
        if (!$this->signature_path) {
            return null;
        }
        
        if (filter_var($this->signature_path, FILTER_VALIDATE_URL)) {
            return $this->signature_path;
        }
        
        if (str_starts_with($this->signature_path, 'storage/')) {
            return asset($this->signature_path);
        }
        
        return asset('storage/' . $this->signature_path);
    }

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo_path) {
            return null;
        }
        
        if (filter_var($this->photo_path, FILTER_VALIDATE_URL)) {
            return $this->photo_path;
        }
        
        if (str_starts_with($this->photo_path, 'storage/')) {
            return asset($this->photo_path);
        }
        
        return asset('storage/' . $this->photo_path);
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
            'student' => 'Student',
            'staff' => 'Staff',
            'visitor' => 'Visitor',
            'contractor' => 'Contractor',
            default => ucfirst(str_replace('_', ' ', $this->holder_type)) ?: 'Unknown',
        };
    }

    // NEW: Get returned by type label
    public function getReturnedByTypeLabelAttribute()
    {
        return match($this->returned_by_type) {
            'hr' => 'HR Staff',
            'perm_manual' => 'Permanent Staff (Manual)',
            'temp' => 'Temporary Staff',
            'student' => 'Student',
            'staff' => 'Staff',
            'visitor' => 'Visitor',
            'contractor' => 'Contractor',
            default => ucfirst(str_replace('_', ' ', $this->returned_by_type)) ?: 'Unknown',
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

    // NEW: Safe method to get returned by name
    public function getReturnedByDisplayNameAttribute()
    {
        return $this->returned_by_name ?? $this->holder_name ?? 'Unknown Returner';
    }

    // NEW: Safe method to get returned by phone
    public function getReturnedByDisplayPhoneAttribute()
    {
        return $this->returned_by_phone ?? $this->holder_phone ?? 'N/A';
    }

    // NEW: Appended attribute for easy access
    public function getIsReturnedByDifferentPersonAttribute()
    {
        return $this->isReturnedByDifferentPerson();
    }

    /**
     * Get the action with badge HTML for display
     */
    public function getActionBadgeAttribute()
    {
        if ($this->isCheckout()) {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                <i class="fas fa-arrow-right mr-1"></i> Checkout
            </span>';
        } else {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <i class="fas fa-arrow-left mr-1"></i> Checkin
            </span>';
        }
    }

    /**
     * Check if this log can be checked in
     */
    public function canBeCheckedIn()
    {
        return $this->isCheckout() && $this->isOpenCheckout();
    }

    /**
     * Get related checkout log for a checkin
     */
    public function getRelatedCheckoutLog()
    {
        if ($this->isCheckin()) {
            return $this->returnedFromLog;
        }
        
        return null;
    }

    /**
     * Get the status of the key log
     */
    public function getStatusAttribute()
    {
        if ($this->isCheckin()) {
            return 'returned';
        }
        
        if ($this->isOverdue()) {
            return 'overdue';
        }
        
        if ($this->isOpenCheckout()) {
            return 'checked_out';
        }
        
        return 'unknown';
    }

    /**
     * Get status badge for display
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'checked_out' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Checked Out</span>',
            'returned' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Returned</span>',
            'overdue' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Overdue</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>',
        };
    }

    // NEW: Get return information for display
    public function getReturnInfoAttribute()
    {
        if (!$this->isCheckin()) return null;

        if ($this->isReturnedByDifferentPerson()) {
            return [
                'type' => 'different_person',
                'message' => "Returned by {$this->returned_by_display_name} on behalf of {$this->holder_display_name}",
                'original_holder' => [
                    'name' => $this->holder_display_name,
                    'phone' => $this->holder_display_phone,
                ],
                'returned_by' => [
                    'name' => $this->returned_by_display_name,
                    'phone' => $this->returned_by_display_phone,
                ],
            ];
        }

        return [
            'type' => 'same_person',
            'message' => "Returned by {$this->holder_display_name}",
            'holder' => [
                'name' => $this->holder_display_name,
                'phone' => $this->holder_display_phone,
            ],
        ];
    }

    // NEW: Method to set returned by information
    public function setReturnedBy($type, $id, $name, $phone)
    {
        $this->update([
            'returned_by_type' => $type,
            'returned_by_id' => $id,
            'returned_by_name' => $name,
            'returned_by_phone' => $phone,
            'actual_return_at' => now(),
        ]);

        return $this;
    }

    // NEW: Method to set same person return
    public function setSamePersonReturn()
    {
        return $this->setReturnedBy(
            $this->holder_type,
            $this->holder_id,
            $this->holder_name,
            $this->holder_phone
        );
    }
}