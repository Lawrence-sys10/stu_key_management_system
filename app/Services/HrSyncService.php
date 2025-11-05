<?php

namespace App\Services;

use App\Models\HrStaff;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrSyncService
{
    public function syncStaff()
    {
        $lastSync = Setting::getValue('hr.last_sync', null);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.hr_api.key'),
            'Accept' => 'application/json',
        ])->get(config('services.hr_api.base_url') . '/staff', [
            'updated_since' => $lastSync,
        ]);

        if (!$response->successful()) {
            throw new \Exception('HR API request failed: ' . $response->body());
        }

        $staffData = $response->json();
        $newRecords = 0;
        $updatedRecords = 0;

        foreach ($staffData['data'] ?? [] as $staff) {
            $existing = HrStaff::where('staff_id', $staff['staff_id'])->first();

            if ($existing) {
                $existing->update([
                    'name' => $staff['name'],
                    'phone' => $staff['phone'],
                    'dept' => $staff['department'],
                    'email' => $staff['email'],
                    'status' => $staff['status'],
                    'synced_at' => now(),
                ]);
                $updatedRecords++;
            } else {
                HrStaff::create([
                    'staff_id' => $staff['staff_id'],
                    'name' => $staff['name'],
                    'phone' => $staff['phone'],
                    'dept' => $staff['department'],
                    'email' => $staff['email'],
                    'status' => $staff['status'],
                    'source' => 'api',
                    'synced_at' => now(),
                ]);
                $newRecords++;
            }
        }

        Setting::setValue('hr.last_sync', now()->toISOString());

        return [
            'new_records' => $newRecords,
            'updated_records' => $updatedRecords,
            'total_records' => count($staffData['data'] ?? []),
        ];
    }

    public function testConnection()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hr_api.key'),
                'Accept' => 'application/json',
            ])->get(config('services.hr_api.base_url') . '/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('HR API connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
