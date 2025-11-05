<?php

namespace App\Jobs;

use App\Models\KeyLog;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCheckoutNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $keyLog;
    public $tries = 3;

    public function __construct(KeyLog $keyLog)
    {
        $this->keyLog = $keyLog->withoutRelations();
    }

    public function handle(NotificationService $notificationService)
    {
        try {
            $notificationService->sendCheckoutNotification($this->keyLog);
        } catch (\Exception $e) {
            \Log::error('Failed to send checkout notification: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Exception $exception)
    {
        \Log::error('SendCheckoutNotification job failed: ' . $exception->getMessage());
    }
}
