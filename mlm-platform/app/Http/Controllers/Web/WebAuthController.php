<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WebAuthController extends Controller
{
    public function __construct(protected RegistrationService $registrationService) {}

    public function showLogin() { return view('login'); }
    public function showRegister() { return view('register'); }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['phone' => 'Invalid credentials.']);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|regex:/^01[3-9]\d{8}$/',
            'password' => 'required|string|min:8|confirmed',
            'referrer_id' => 'required|string|exists:users,referral_id',
        ]);

        // For this "basic" version, we skip the OTP/Payment step and create an ACTIVE user directly 
        // OR we can create an inactive one. Let's create ACTIVE for easy testing as requested.
        
        $referrer = User::where('referral_id', $data['referrer_id'])->first();
        
        $user = User::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'referred_by' => $referrer->id,
            'status' => 'active', // Direct active for testing
        ]);

        // Complete registration logic (Referral tree, Wallet)
        $this->registrationService->completeRegistration($user, null);

        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'Account created successfully!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
