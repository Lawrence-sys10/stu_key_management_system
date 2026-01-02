<?php

namespace App\Services;

use App\Models\HrStaff;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrSyncService
{
    /**
     * Lookup staff/student from IRMTS unified API
     * This replaces direct HR API calls and uses IRMTS as the data hub
     * 
     * @param string $identifier Staff number, student ID, email, phone, or name
     * @param string $entity Entity type: 'staff', 'student', 'part_time_staff'
     * @return array|null Staff/student data or null if not found
     */
    public function lookupStaff($identifier, $entity = 'staff')
    {
        $irmtsUrl = config('services.irmts_api.base_url', env('IRMTS_API_URL', ''));
        $irmtsToken = config('services.irmts_api.key', env('IRMTS_API_SECRET', ''));

        if (!$irmtsUrl || !$irmtsToken) {
            Log::warning('IRMTS API not configured', [
                'url' => $irmtsUrl,
                'has_token' => !empty($irmtsToken),
            ]);
            return null;
        }

        try {
            $response = Http::timeout(30)->withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $irmtsToken,
                    'Accept' => 'application/json',
                ])
                ->asForm()
                ->post($irmtsUrl . '/api/lookup', [
                    'index_staff_id' => $identifier,
                    'entity' => $entity,
                ]);

            if (!$response->successful()) {
                Log::error('IRMTS API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            if ($data && isset($data['status']) && $data['status'] == 200 && isset($data['data'])) {
                return $data['data'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('IRMTS API lookup error: ' . $e->getMessage(), [
                'identifier' => $identifier,
                'entity' => $entity,
            ]);
            return null;
        }
    }

    /**
     * Sync staff data from IRMTS API
     * Note: This is a simplified version - IRMTS doesn't provide bulk sync
     * For bulk operations, you would need to maintain a list of identifiers
     */
    public function syncStaff()
    {
        // This method is kept for backward compatibility
        // For bulk sync, you would need to iterate through known staff IDs
        // and call lookupStaff() for each
        
        Log::info('Bulk sync not supported via IRMTS API. Use lookupStaff() for individual lookups.');
        
        return [
            'new_records' => 0,
            'updated_records' => 0,
            'total_records' => 0,
            'message' => 'Bulk sync not available. Use individual lookups via lookupStaff() method.',
        ];
    }

    /**
     * Sync staff data from IRMTS to local database
     * This is called when staff is found via IRMTS but not in local DB
     * 
     * @param array $irmtsData Staff data from IRMTS API
     * @return \App\Models\HrStaff|null Created or updated staff record
     */
    public function syncStaffFromIrmts(array $irmtsData)
    {
        if (!isset($irmtsData['staff_id'])) {
            return null;
        }

        try {
            $staff = \App\Models\HrStaff::where('staff_id', $irmtsData['staff_id'])->first();

            $staffData = [
                'staff_id' => $irmtsData['staff_id'],
                'name' => $irmtsData['name'] ?? 'Unknown',
                'phone' => $irmtsData['phone'] ?? '',
                'email' => $irmtsData['email'] ?? null,
                'dept' => $irmtsData['department'] ?? 'N/A',
                'status' => $irmtsData['status'] ?? 'active',
                'source' => 'irmts',
                'synced_at' => now(),
            ];

            if ($staff) {
                $staff->update($staffData);
            } else {
                $staff = \App\Models\HrStaff::create($staffData);
            }

            Log::info('Synced staff from IRMTS to local database', [
                'staff_id' => $irmtsData['staff_id'],
                'name' => $staffData['name'],
            ]);

            return $staff;
        } catch (\Exception $e) {
            Log::error('Failed to sync staff from IRMTS', [
                'staff_data' => $irmtsData,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Test connection to IRMTS API
     */
    public function testConnection()
    {
        try {
            $irmtsUrl = config('services.irmts_api.base_url', env('IRMTS_API_URL', ''));
            $irmtsToken = config('services.irmts_api.key', env('IRMTS_API_SECRET', ''));

            if (!$irmtsUrl || !$irmtsToken) {
                return false;
            }

            $response = Http::timeout(10)->withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $irmtsToken,
                    'Accept' => 'application/json',
                ])
                ->get($irmtsUrl . '/api/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('IRMTS API connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
