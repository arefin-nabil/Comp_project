<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use App\Models\Referral;
use App\Models\User;
use App\Models\Club;
use App\Models\Withdrawal;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load(['wallet', 'clubs']);
        return view('dashboard', compact('user'));
    }

    public function wallet(Request $request)
    {
        $user = $request->user()->load('wallet');
        $transactions = WalletTransaction::where('wallet_id', $user->wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('wallet', compact('user', 'transactions'));
    }

    public function network(Request $request)
    {
        $user = $request->user();
        $directReferrals = User::where('referred_by', $user->id)->get();
        
        $levelCounts = [];
        for ($i = 1; $i <= 10; $i++) {
            $levelField = $i === 1 ? 'referrer_id' : "level{$i}_id";
            $levelCounts[$i] = Referral::where($levelField, $user->id)->count();
        }
        
        return view('network', compact('user', 'directReferrals', 'levelCounts'));
    }
}
