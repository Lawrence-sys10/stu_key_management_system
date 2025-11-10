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
