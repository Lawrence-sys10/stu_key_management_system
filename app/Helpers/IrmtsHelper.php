<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IrmtsHelper
{
    /**
     * Lookup staff/student from IRMTS unified API
     * 
     * @param string $identifier Staff number, student ID, email, phone, or name
     * @param string $entity Entity type: 'staff', 'student', 'part_time_staff'
     * @return array|null Staff/student data or null if not found
     */
    public static function lookup($identifier, $entity = 'staff')
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
                Log::debug('IRMTS API request failed', [
                    'status' => $response->status(),
                    'identifier' => $identifier,
                    'entity' => $entity,
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
}

