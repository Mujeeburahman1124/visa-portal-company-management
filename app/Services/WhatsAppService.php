<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Env;
use Exception;

class WhatsAppService
{
    /**
     * Send a WhatsApp message via the official Meta WhatsApp Business Cloud API.
     *
     * @param array $params [
     *   'phoneNumber' => string,
     *   'templateName' => ?string (Meta approved template name),
     *   'languageCode' => ?string (default: 'en_US'),
     *   'variables' => ?array (ordered parameters or key-value map),
     *   'messageText' => ?string (fallback raw text message),
     *   'data' => ?array (key-value context data)
     * ]
     * @return array [
     *   'success' => bool,
     *   'message_id' => ?string (wamid.XXX),
     *   'normalized_phone' => ?string,
     *   'provider' => string,
     *   'error' => ?string,
     *   'response' => ?array,
     *   'simulated' => bool
     * ]
     */
    public static function send(array $params): array
    {
        $rawPhone = trim($params['phoneNumber'] ?? '');
        $templateName = trim($params['templateName'] ?? '');
        $languageCode = trim($params['languageCode'] ?? 'en_US');
        $variables = $params['variables'] ?? [];
        $messageText = trim($params['messageText'] ?? '');
        $data = $params['data'] ?? [];

        // 1. Phone Number Validation & E.164 Normalization
        $phoneResult = self::normalizePhoneNumber($rawPhone);
        if (!$phoneResult['valid']) {
            return [
                'success' => false,
                'message_id' => null,
                'normalized_phone' => null,
                'provider' => 'meta_whatsapp',
                'error' => "Invalid phone number format: '{$rawPhone}' — " . $phoneResult['error'],
                'response' => null,
                'simulated' => false,
            ];
        }

        $recipientPhone = $phoneResult['normalized']; // E.g. '94771234567'

        // 2. Fetch API Credentials from Environment / Settings
        $provider = strtolower((string)Env::get('WHATSAPP_PROVIDER', 'meta'));
        $accessToken = (string)Env::get('WHATSAPP_ACCESS_TOKEN', '');
        $phoneNumberId = (string)Env::get('WHATSAPP_PHONE_NUMBER_ID', '');
        $apiVersion = (string)Env::get('WHATSAPP_API_VERSION', 'v20.0');
        $apiUrl = rtrim((string)Env::get('WHATSAPP_API_URL', 'https://graph.facebook.com'), '/');
        $envMode = strtolower((string)Env::get('NOTIFICATION_ENV', 'development'));

        // 3. Safe Simulation / Sandbox Mode
        // If credentials are not configured or in development/testing mode without active tokens, simulate safely
        if (empty($accessToken) || empty($phoneNumberId) || $envMode === 'development' && empty($accessToken)) {
            $simWamid = 'wamid.HBg' . strtoupper(bin2hex(random_bytes(14))) . '==';
            return [
                'success' => true,
                'message_id' => $simWamid,
                'normalized_phone' => '+' . $recipientPhone,
                'provider' => 'meta_whatsapp (simulated)',
                'error' => null,
                'response' => [
                    'messaging_product' => 'whatsapp',
                    'contacts' => [['input' => $recipientPhone, 'wa_id' => $recipientPhone]],
                    'messages' => [['id' => $simWamid]],
                    'simulated' => true,
                ],
                'simulated' => true,
            ];
        }

        // 4. Construct Meta WhatsApp Business API Payload
        $endpoint = "{$apiUrl}/{$apiVersion}/{$phoneNumberId}/messages";
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipientPhone,
        ];

        if (!empty($templateName)) {
            // Build template component body parameters
            $bodyParameters = [];
            
            // If variables are provided as key-value, convert to positional parameters
            foreach ($variables as $v) {
                $bodyParameters[] = [
                    'type' => 'text',
                    'text' => (string)$v,
                ];
            }

            $payload['type'] = 'template';
            $payload['template'] = [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ];

            if (!empty($bodyParameters)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => $bodyParameters,
                    ]
                ];
            }
        } else {
            // Text message payload
            $interpolatedText = !empty($messageText) ? EmailService::interpolate($messageText, $data) : 'Notification from ' . App::COMPANY_NAME;
            $payload['type'] = 'text';
            $payload['text'] = [
                'preview_url' => true,
                'body' => $interpolatedText,
            ];
        }

        // 5. Execute HTTP Request via cURL
        try {
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json",
                "User-Agent: VisaTrack-WhatsApp-Client/1.0"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            unset($ch);

            if ($responseBody === false || !empty($curlError)) {
                throw new Exception("cURL error connecting to Meta WhatsApp API: {$curlError}");
            }

            $responseData = json_decode($responseBody, true) ?: [];

            if ($httpCode >= 200 && $httpCode < 300 && !empty($responseData['messages'][0]['id'])) {
                $wamid = $responseData['messages'][0]['id'];
                return [
                    'success' => true,
                    'message_id' => $wamid,
                    'normalized_phone' => '+' . $recipientPhone,
                    'provider' => 'meta_whatsapp',
                    'error' => null,
                    'response' => $responseData,
                    'simulated' => false,
                ];
            } else {
                $metaError = $responseData['error']['message'] ?? "HTTP {$httpCode}: {$responseBody}";
                return [
                    'success' => false,
                    'message_id' => null,
                    'normalized_phone' => '+' . $recipientPhone,
                    'provider' => 'meta_whatsapp',
                    'error' => "Meta API Error: {$metaError}",
                    'response' => $responseData,
                    'simulated' => false,
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message_id' => null,
                'normalized_phone' => '+' . $recipientPhone,
                'provider' => 'meta_whatsapp',
                'error' => $e->getMessage(),
                'response' => null,
                'simulated' => false,
            ];
        }
    }

    /**
     * Strict Phone Number Normalizer & E.164 Formatter.
     * Handles Sri Lankan national format (077XXXXXXX) as well as global international numbers.
     * Returns country code + subscriber digits without leading plus, as required by Meta Cloud API.
     */
    public static function normalizePhoneNumber(string $rawNumber): array
    {
        $cleaned = trim($rawNumber);
        
        if (empty($cleaned)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Phone number is empty'
            ];
        }

        // Remove spaces, hyphens, brackets, and periods
        $digitsOnly = preg_replace('/[^\d+]/', '', $cleaned);

        // Remove double zero prefix (00) -> (+)
        if (str_starts_with($digitsOnly, '00')) {
            $digitsOnly = '+' . substr($digitsOnly, 2);
        }

        // 1. Handle Sri Lankan numbers
        // Format: 07XXXXXXXX (10 digits starting with 07)
        if (preg_match('/^0(7[0-9]{8})$/', $digitsOnly, $matches)) {
            return [
                'valid' => true,
                'normalized' => '94' . $matches[1],
                'country_code' => '94',
                'formatted' => '+94 ' . substr($matches[1], 0, 2) . ' ' . substr($matches[1], 2, 3) . ' ' . substr($matches[1], 5),
                'error' => null
            ];
        }

        // Format: 7XXXXXXXX (9 digits starting with 7) -> assume Sri Lanka
        if (preg_match('/^(7[0-9]{8})$/', $digitsOnly, $matches)) {
            return [
                'valid' => true,
                'normalized' => '94' . $matches[1],
                'country_code' => '94',
                'formatted' => '+94 ' . substr($matches[1], 0, 2) . ' ' . substr($matches[1], 2, 3) . ' ' . substr($matches[1], 5),
                'error' => null
            ];
        }

        // Format: +947XXXXXXXX or 947XXXXXXXX
        if (preg_match('/^\+?94(7[0-9]{8})$/', $digitsOnly, $matches)) {
            return [
                'valid' => true,
                'normalized' => '94' . $matches[1],
                'country_code' => '94',
                'formatted' => '+94 ' . substr($matches[1], 0, 2) . ' ' . substr($matches[1], 2, 3) . ' ' . substr($matches[1], 5),
                'error' => null
            ];
        }

        // 2. Handle International E.164 Numbers with leading '+'
        if (str_starts_with($digitsOnly, '+')) {
            $digits = substr($digitsOnly, 1);
            if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                return [
                    'valid' => true,
                    'normalized' => $digits,
                    'country_code' => substr($digits, 0, 3),
                    'formatted' => '+' . $digits,
                    'error' => null
                ];
            }
        }

        // 3. Handle standard international numeric string (8-15 digits without +)
        $digits = preg_replace('/[^\d]/', '', $digitsOnly);
        if (strlen($digits) >= 9 && strlen($digits) <= 15) {
            return [
                'valid' => true,
                'normalized' => $digits,
                'country_code' => substr($digits, 0, 3),
                'formatted' => '+' . $digits,
                'error' => null
            ];
        }

        return [
            'valid' => false,
            'normalized' => null,
            'error' => "Invalid phone number length or character sequence (Provided: {$rawNumber})"
        ];
    }
}
