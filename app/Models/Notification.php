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
