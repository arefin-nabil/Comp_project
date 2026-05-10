<?php

namespace App\Services\Auth;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\SMS\SmsService;

class OtpService
{
    public function __construct(protected SmsService $smsService) {}

    /**
     * Generate and send OTP via SMS.
     * Stores in Redis (via Cache) with 120s TTL and falls back to DB.
     * Rate limited to 1 per minute per phone per action.
     */
    public function sendOtp(string $phone, string $action): void
    {
        $rateLimitKey = "otp_rate_limit:{$action}:{$phone}";
        if (Cache::has($rateLimitKey)) {
            throw new \Exception("Please wait before requesting another OTP.");
        }

        $code = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(2);

        // Store in DB for audit/fallback
        OtpCode::create([
            'phone' => $phone,
            'action' => $action,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        // Store in Redis (primary fast access)
        $redisKey = "otp:{$action}:{$phone}";
        Cache::put($redisKey, ['code' => $code, 'attempts' => 0], $expiresAt);
        
        // Block new requests for 60 seconds
        Cache::put($rateLimitKey, true, now()->addSeconds(60));

        // Dispatch SMS
        $message = "Your MLM Platform OTP is {$code}. Valid for 2 minutes.";
        $this->smsService->send($phone, $message);
    }

    /**
     * Verify the OTP.
     */
    public function verifyOtp(string $phone, string $action, string $code): bool
    {
        $redisKey = "otp:{$action}:{$phone}";
        $cached = Cache::get($redisKey);

        if (!$cached) {
            // Check DB fallback
            $dbOtp = OtpCode::valid()->where('phone', $phone)->where('action', $action)->latest()->first();
            if (!$dbOtp) {
                return false;
            }
            
            if ($dbOtp->code !== $code) {
                $dbOtp->increment('attempts');
                return false;
            }

            $dbOtp->update(['is_used' => true, 'used_at' => now()]);
            return true;
        }

        if ($cached['attempts'] >= 3) {
            Cache::forget($redisKey);
            return false;
        }

        if ($cached['code'] !== $code) {
            $cached['attempts']++;
            Cache::put($redisKey, $cached, Cache::getStore()->getRedis()->ttl(Cache::getPrefix() . $redisKey));
            return false;
        }

        Cache::forget($redisKey);
        // Async update DB
        OtpCode::valid()->where('phone', $phone)->where('action', $action)->latest()->update([
            'is_used' => true,
            'used_at' => now()
        ]);

        return true;
    }
}
