<?php

namespace App\Traits;

use App\Models\KeyLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HolderTrait
{
    public function keyLogs(): MorphMany
    {
        return $this->morphMany(KeyLog::class, 'holder');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
