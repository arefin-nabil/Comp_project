<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        $driverName = config('sms.default');
        
        if ($driverName === 'log') {
            Log::info("SMS to {$phone}: {$message}");
            return true;
        }

        if ($driverName === 'ssl_wireless') {
            return app(Drivers\SslWirelessDriver::class)->send($phone, $message);
        }

        Log::warning("Unknown SMS driver configured: {$driverName}");
        return false;
    }
}
