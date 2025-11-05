<?php

namespace App\Jobs;

use App\Models\KeyLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckOverdueKeys implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $overdueKeys = KeyLog::overdue()->get();

        foreach ($overdueKeys as $keyLog) {
            // Check if we already sent a notification for this overdue key today
            $today = now()->format('Y-m-d');
            $existingNotification = \App\Models\Notification::where('key_log_id', $keyLog->id)
                ->where('template_key', 'overdue_notice')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingNotification) {
                SendOverdueNotification::dispatch($keyLog);
            }
        }

        Log::info('Overdue keys check completed', ['count' => $overdueKeys->count()]);
    }
}
