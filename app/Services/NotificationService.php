<?php

namespace App\Services;

use App\Models\KeyLog;
use App\Models\Notification;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendCheckoutNotification(KeyLog $keyLog)
    {
        if (!Setting::getValue('notifications.checkout_enabled', false)) {
            return;
        }

        $template = Setting::getValue('notifications.checkout_template', 
            'Key {KEY_LABEL} ({LOCATION}) checked out by {NAME} at {TIME}. Expected return: {DUE}.');

        $message = $this->replaceTemplateVariables($template, $keyLog);

        return $this->sendSms($keyLog->holder_phone, $message, 'checkout_notice', $keyLog);
    }

    public function sendReturnNotification(KeyLog $keyLog)
    {
        if (!Setting::getValue('notifications.return_enabled', false)) {
            return;
        }

        $template = Setting::getValue('notifications.return_template',
            'Thanks, {NAME}. Key {KEY_LABEL} returned at {TIME}.');

        $message = $this->replaceTemplateVariables($template, $keyLog);

        return $this->sendSms($keyLog->holder_phone, $message, 'return_confirm', $keyLog);
    }

    public function sendOverdueNotification(KeyLog $keyLog)
    {
        if (!Setting::getValue('notifications.overdue_enabled', false)) {
            return;
        }

        $template = Setting::getValue('notifications.overdue_template',
            'Reminder: Key {KEY_LABEL} is overdue. Please return ASAP.');

        $message = $this->replaceTemplateVariables($template, $keyLog);

        return $this->sendSms($keyLog->holder_phone, $message, 'overdue_notice', $keyLog);
    }

    private function replaceTemplateVariables($template, KeyLog $keyLog)
    {
        $variables = [
            '{KEY_LABEL}' => $keyLog->key->label,
            '{KEY_CODE}' => $keyLog->key->code,
            '{LOCATION}' => $keyLog->key->location->full_address,
            '{NAME}' => $keyLog->holder_name,
            '{PHONE}' => $keyLog->holder_phone,
            '{TIME}' => $keyLog->created_at->format('M j, Y g:i A'),
            '{DUE}' => $keyLog->expected_return_at ? $keyLog->expected_return_at->format('M j, Y g:i A') : 'Not specified',
            '{OFFICER}' => $keyLog->receiver_name,
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    private function sendSms($to, $message, $templateKey, KeyLog $keyLog)
    {
        $provider = config('services.sms.default', 'hubtel');

        try {
            if ($provider === 'hubtel') {
                return $this->sendViaHubtel($to, $message, $templateKey, $keyLog);
            }

            // Add other providers here (twilio, etc.)
            Log::warning("SMS provider not implemented: {$provider}");

        } catch (\Exception $e) {
            Log::error("SMS sending failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function sendViaHubtel($to, $message, $templateKey, KeyLog $keyLog)
    {
        $clientId = config('services.hubtel.client_id');
        $clientSecret = config('services.hubtel.client_secret');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Hubtel credentials not configured');
        }

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->post('https://sms.hubtel.com/v1/messages/send', [
                'from' => config('services.hubtel.sender_id', 'STU-Keys'),
                'to' => $this->formatPhoneNumber($to),
                'content' => $message,
            ]);

        $notification = Notification::create([
            'key_log_id' => $keyLog->id,
            'channel' => 'sms',
            'to' => $to,
            'template_key' => $templateKey,
            'payload_json' => [
                'message' => $message,
                'provider' => 'hubtel',
                'provider_response' => $response->json(),
            ],
            'status' => $response->successful() ? 'sent' : 'failed',
            'sent_at' => $response->successful() ? now() : null,
            'error' => $response->successful() ? null : $response->body(),
        ]);

        if (!$response->successful()) {
            throw new \Exception('Hubtel API error: ' . $response->body());
        }

        return $notification;
    }

    private function formatPhoneNumber($phone)
    {
        // Ensure phone number is in E.164 format
        $phone = preg_replace('/\D/', '', $phone);
        
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '233' . substr($phone, 1);
        }
        
        if (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
            return '233' . $phone;
        }

        return $phone;
    }
}
