<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * GekyChat Platform API Integration Service
 * 
 * This service handles sending messages to users via GekyChat platform API.
 * It manages authentication, user lookup, conversation creation, and message sending.
 */
class GekyChatService
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $accessToken = null;
    protected int $systemBotUserId;

    public function __construct()
    {
        // Default to api subdomain - platform API routes are on api.gekychat.test
        // Routes are at: api.gekychat.test/platform/oauth/token (no /api prefix)
        $defaultUrl = env('APP_ENV') === 'local' 
            ? 'http://api.gekychat.test' 
            : 'https://api.gekychat.com';
        $this->baseUrl = rtrim(config('services.gekychat.base_url', $defaultUrl), '/');
        $this->clientId = config('services.gekychat.client_id');
        $this->clientSecret = config('services.gekychat.client_secret');
        $this->systemBotUserId = (int) config('services.gekychat.system_bot_user_id', 0);
        
        // Validate required credentials
        if (empty($this->clientId) || empty($this->clientSecret)) {
            Log::warning('GekyChat credentials not configured', [
                'has_client_id' => !empty($this->clientId),
                'has_client_secret' => !empty($this->clientSecret),
            ]);
        }
    }

    /**
     * Send a message to a user by phone number
     * Uses the simplified /send-to-phone endpoint if available, otherwise uses multi-step process
     * 
     * @param string $phoneNumber Phone number in any format (will be normalized)
     * @param string $message Message content to send
     * @param array $metadata Optional metadata to attach to message
     * @return array Result with success status and message/conversation IDs
     */
    public function sendMessageByPhone(string $phoneNumber, string $message, array $metadata = []): array
    {
        try {
            Log::info('GekyChat sendMessageByPhone: Starting', [
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
                'base_url' => $this->baseUrl,
            ]);
            
            // Get or refresh access token
            $token = $this->getAccessToken();
            if (!$token) {
                $error = 'Failed to obtain access token. Check logs for OAuth details.';
                Log::error('GekyChat sendMessageByPhone: Token failed', [
                    'phone' => $phoneNumber,
                    'base_url' => $this->baseUrl,
                ]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            // Normalize phone number
            $normalizedPhone = $this->normalizePhone($phoneNumber);

            // Try simplified endpoint first (works for privileged clients)
            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$this->baseUrl}/platform/messages/send-to-phone", [
                    'phone' => $normalizedPhone,
                    'body' => $message,
                    'metadata' => $metadata ?? [],
                    'bot_user_id' => $this->systemBotUserId > 0 ? $this->systemBotUserId : null,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['data']['message_id'] ?? null,
                    'conversation_id' => $data['data']['conversation_id'] ?? null,
                    'user_id' => $data['data']['user_id'] ?? null,
                ];
            }

            // If simplified endpoint fails (e.g., not privileged), fall back to multi-step process
            if ($response->status() === 404) {
                return $this->sendMessageMultiStep($normalizedPhone, $message, $metadata);
            }

            // Other errors
            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['error'] ?? 'API request failed',
                'status' => $response->status(),
                'body' => $response->body()
            ];

        } catch (\Throwable $e) {
            Log::error('GekyChat sendMessageByPhone failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Multi-step process for sending messages (fallback)
     */
    protected function sendMessageMultiStep(string $normalizedPhone, string $message, array $metadata = []): array
    {
        // Find or create user by phone
        $userId = $this->findOrCreateUser($normalizedPhone);
        if (!$userId) {
            return [
                'success' => false,
                'error' => 'Failed to find or create user'
            ];
        }

        // Find or create conversation between system bot and user
        $conversationId = $this->findOrCreateConversation($userId);
        if (!$conversationId) {
            return [
                'success' => false,
                'error' => 'Failed to find or create conversation'
            ];
        }

        // Send the message
        return $this->sendMessageToConversation($conversationId, $message, $metadata);
    }

    /**
     * Get OAuth access token using client credentials
     */
    protected function getAccessToken(): ?string
    {
        // Check cache first (use client_id in key to avoid conflicts between systems)
        $cacheKey = 'gekychat_access_token_' . md5($this->clientId ?? 'default');
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::debug('GekyChat OAuth: Using cached token', [
                'base_url' => $this->baseUrl,
                'client_id' => substr($this->clientId ?? '', 0, 10) . '...',
            ]);
            return $cached;
        }

        // Validate credentials are set
        if (empty($this->clientId) || empty($this->clientSecret)) {
            Log::error('GekyChat OAuth: Missing credentials', [
                'has_client_id' => !empty($this->clientId),
                'has_client_secret' => !empty($this->clientSecret),
                'base_url' => $this->baseUrl,
            ]);
            return null;
        }

        try {
            // Routes are defined with prefix('platform'), so endpoint is /platform/oauth/token
            $endpoint = "{$this->baseUrl}/platform/oauth/token";
            
            $payload = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ];
            
            Log::info('GekyChat OAuth: Requesting token', [
                'endpoint' => $endpoint,
                'base_url' => $this->baseUrl,
                'client_id' => substr($this->clientId ?? '', 0, 10) . '...',
                'has_client_secret' => !empty($this->clientSecret),
            ]);

            $response = Http::asForm()
                ->timeout(10)
                ->post($endpoint, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $isSuccessful = $response->successful();

            if ($isSuccessful) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                
                if ($token) {
                    // Cache token for 1 hour (adjust based on actual expiry)
                    // Use client_id in key to avoid conflicts
                    $cacheKey = 'gekychat_access_token_' . md5($this->clientId ?? 'default');
                    Cache::put($cacheKey, $token, now()->addHour());
                    
                    Log::info('GekyChat OAuth: Token obtained successfully', [
                        'endpoint' => $endpoint,
                        'token_preview' => substr($token, 0, 20) . '...',
                    ]);
                    
                    return $token;
                } else {
                    Log::warning('GekyChat OAuth: No access_token in response', [
                        'endpoint' => $endpoint,
                        'status' => $statusCode,
                        'response_data' => $data ?? null,
                    ]);
                }
            } else {
                // Extract error message from response
                $errorMessage = 'Unknown error';
                $errorData = null;
                
                try {
                    $errorData = $response->json();
                    if (isset($errorData['error'])) {
                        $errorMessage = is_string($errorData['error']) 
                            ? $errorData['error'] 
                            : json_encode($errorData['error']);
                    } elseif (isset($errorData['message'])) {
                        $errorMessage = $errorData['message'];
                    }
                } catch (\Exception $e) {
                    // Response might be HTML, extract text if possible
                    if (strlen($responseBody) > 0) {
                        $errorMessage = 'HTTP ' . $statusCode . ' - ' . substr(strip_tags($responseBody), 0, 200);
                    }
                }
                
                Log::warning('GekyChat OAuth failed', [
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'response_preview' => substr($responseBody, 0, 500),
                    'error_data' => $errorData,
                ]);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('GekyChat OAuth error', [
                'endpoint' => $endpoint ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Normalize phone number to standard format
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $plus = str_starts_with($phone, '+') ? '+' : '';
        $digits = preg_replace('/\D+/', '', $phone);
        
        // Handle Ghana numbers: convert 0xxx to +233xxx
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+233' . substr($digits, 1);
        }
        
        // If no country code and starts with 0, assume Ghana
        if (strlen($digits) === 10 && !$plus) {
            return '+233' . substr($digits, 1);
        }
        
        return $plus . $digits;
    }

    /**
     * Find or create a user by phone number
     */
    protected function findOrCreateUser(string $normalizedPhone): ?int
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            // Try to find user by phone
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$this->baseUrl}/platform/users/by-phone", [
                    'phone' => $normalizedPhone
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['id'])) {
                    $userId = (int) $data['data']['id'];
                    
                    // Log if user was auto-created
                    if (isset($data['data']['auto_created']) && $data['data']['auto_created']) {
                        Log::info('GekyChat user auto-created', [
                            'phone' => $normalizedPhone,
                            'user_id' => $userId
                        ]);
                    }
                    
                    return $userId;
                }
            }

            // If user not found, log warning
            if ($response->status() === 404) {
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'User not found';
                
                Log::warning('GekyChat user not found', [
                    'phone' => $normalizedPhone,
                    'error' => $errorMessage,
                ]);
            } else {
                Log::warning('GekyChat user lookup failed', [
                    'phone' => $normalizedPhone,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('GekyChat findOrCreateUser error', [
                'phone' => $normalizedPhone,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find or create a conversation between system bot and user
     */
    protected function findOrCreateConversation(int $userId): ?int
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            // Try to find existing conversation
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$this->baseUrl}/platform/conversations/find-or-create", [
                    'user_id' => $userId,
                    'bot_user_id' => $this->systemBotUserId,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['conversation_id'])) {
                    return (int) $data['data']['conversation_id'];
                }
            }

            Log::warning('GekyChat conversation lookup/creation failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('GekyChat findOrCreateConversation error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send message to a specific conversation
     */
    protected function sendMessageToConversation(int $conversationId, string $message, array $metadata = []): array
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return [
                    'success' => false,
                    'error' => 'No access token'
                ];
            }

            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$this->baseUrl}/platform/messages/send", [
                    'conversation_id' => $conversationId,
                    'body' => $message,
                    'metadata' => $metadata ?? [],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message_id' => $data['data']['message_id'] ?? null,
                    'conversation_id' => $data['data']['conversation_id'] ?? $conversationId,
                ];
            }

            return [
                'success' => false,
                'error' => 'API request failed',
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Throwable $e) {
            Log::error('GekyChat sendMessageToConversation error', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

