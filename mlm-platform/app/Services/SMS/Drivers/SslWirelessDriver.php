<?php

namespace App\Services\SMS\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SslWirelessDriver
{
    public function send(string $phone, string $message): bool
    {
        $config = config('sms.drivers.ssl_wireless');
        
        if (empty($config['api_token']) || empty($config['sid']) || empty($config['csms_id'])) {
            Log::error('SSL Wireless SMS configuration missing.');
            return false;
        }

        try {
            // Adjusting phone format if needed (e.g., must start with 880 for BD)
            $msisdn = $phone;
            if (strlen($phone) === 11 && str_starts_with($phone, '01')) {
                $msisdn = '88' . $phone;
            }

            $response = Http::post($config['url'], [
                'api_token' => $config['api_token'],
                'sid' => $config['sid'],
                'msisdn' => $msisdn,
                'sms' => $message,
                'csms_id' => $config['csms_id'] . '_' . uniqid(),
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('SSL Wireless SMS Failed', ['response' => $response->body()]);
            return false;
            
        } catch (Exception $e) {
            Log::error('SSL Wireless SMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
