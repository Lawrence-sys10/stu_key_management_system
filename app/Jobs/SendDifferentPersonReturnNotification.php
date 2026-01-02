<?php

namespace App\Jobs;

use App\Models\KeyLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class SendDifferentPersonReturnNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public KeyLog $keyLog)
    {
    }

    public function handle()
    {
        // Send notification to security/admin about different person return
        // This could be email, SMS, or in-app notification
        Log::info("Key {$this->keyLog->key->label} returned by different person", [
            'original_holder' => $this->keyLog->holder_name,
            'returned_by' => $this->keyLog->returned_by_name,
            'key' => $this->keyLog->key->label,
            'time' => now()->toDateTimeString(),
        ]);
        
        // You can expand this to send actual notifications
    }
}