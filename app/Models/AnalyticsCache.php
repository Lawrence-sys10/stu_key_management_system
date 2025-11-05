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
