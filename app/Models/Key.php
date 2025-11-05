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
    // FIXED: Use the accessor instead of direct property
    $currentCheckout = $this->current_holder;
    
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

    // FIXED: Accessor method - this is correct
    public function getCurrentHolderAttribute()
    {
        // Use the relationship but load it directly to avoid recursion
        return $this->currentHolder()->first();
    }

    // Helper method to check if key has a current holder
    public function hasCurrentHolder()
    {
        return !is_null($this->current_holder);
    }

    // Get current holder name safely
    public function getCurrentHolderName()
    {
        return $this->current_holder ? $this->current_holder->holder_name : null;
    }

    public function getActiveKeyTag()
    {
        return $this->keyTags()->where('is_active', true)->first();
    }

    // Add a method to get key status with badge class
    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'available' => 'success',
            'checked_out' => 'warning',
            'lost' => 'danger',
            'maintenance' => 'info',
        ];

        return $statuses[$this->status] ?? 'secondary';
    }
}