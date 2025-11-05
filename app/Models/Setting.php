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
