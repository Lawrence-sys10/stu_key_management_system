<?php

namespace App\Jobs;

use App\Models\HrStaff;
use App\Services\HrSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessHrSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function handle(HrSyncService $hrSyncService)
    {
        try {
            if (!config('services.hr_sync.enabled', false)) {
                Log::info('HR Sync is disabled in configuration');
                return;
            }

            $result = $hrSyncService->syncStaff();

            Log::info('HR Sync completed', [
                'new_records' => $result['new_records'],
                'updated_records' => $result['updated_records'],
                'total_records' => $result['total_records'],
            ]);

        } catch (\Exception $e) {
            Log::error('HR Sync failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Exception $exception)
    {
        Log::error('ProcessHrSync job failed: ' . $exception->getMessage());
    }
}
