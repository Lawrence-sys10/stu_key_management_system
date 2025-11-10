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
