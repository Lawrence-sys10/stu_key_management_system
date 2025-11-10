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
