<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\RegistrationPayment;
use App\Services\Auth\RegistrationService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Jobs\VerifyPaymentJob;

class AuthController extends BaseApiController
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected OtpService $otpService
    ) {}

    /**
     * Step 1: Pre-register and Send OTP
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^01[3-9]\d{8}$/',
            'full_name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'referrer_id' => 'required|string|exists:users,referral_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            $lock = $this->registrationService->reservePhone($request->phone);
            if (!$lock) {
                return $this->error('Phone number is already being registered or in use.', 422);
            }

            $this->otpService->sendOtp($request->phone, 'registration');

            return $this->success([], 'OTP sent to your phone. Please verify to continue.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Step 2: Verify OTP and initiate payment
     */
    public function verifyOtpAndPay(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^01[3-9]\d{8}$/',
            'otp' => 'required|string|size:6',
            'payment_method' => 'required|in:bkash,nagad,rocket,upay,offline',
            'transaction_id' => 'required|string|unique:registration_payments,gateway_transaction_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        if (!$this->otpService->verifyOtp($request->phone, $request->otp, 'registration')) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        // Create pending payment
        $payment = RegistrationPayment::create([
            'method' => $request->payment_method,
            'amount' => config('mlm.registration_fee', 100.0000),
            'status' => 'pending',
            'gateway_transaction_id' => $request->transaction_id,
            'idempotency_key' => Str::uuid(),
        ]);

        // Dispatch verification job
        // In real production, the gateway verification would happen here
        VerifyPaymentJob::dispatch($payment, $request->only(['phone', 'full_name', 'password', 'referrer_id']));

        return $this->success([], 'Payment submitted. Your account will be activated once verified.');
    }

    /**
     * Login User
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if ($user->status === 'inactive') {
            return $this->error('Your account is pending activation.', 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user->load('wallet'),
        ], 'Login successful.');
    }

    /**
     * Get Current User Profile
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->success($request->user()->load(['wallet', 'clubs', 'referrer']));
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success([], 'Logged out.');
    }
}
