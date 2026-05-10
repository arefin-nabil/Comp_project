<?php

namespace App\Http\Controllers\Api;

use App\Models\Referral;
use App\Models\TeamIncomeRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MlmController extends BaseApiController
{
    /**
     * Get 10-level referral tree (downline)
     */
    public function downline(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Return downline counts and direct referrals
        $directReferrals = User::where('referred_by', $user->id)
            ->select('id', 'full_name', 'phone', 'created_at')
            ->get();

        // Calculate counts per level (this is a heavy operation, in production we would cache this)
        $levelCounts = [];
        for ($i = 1; $i <= 10; $i++) {
            $levelField = $i === 1 ? 'referrer_id' : "level{$i}_id";
            $levelCounts["level_{$i}"] = Referral::where($levelField, $user->id)->count();
        }

        return $this->success([
            'direct_referrals' => $directReferrals,
            'level_counts' => $levelCounts,
            'total_downline' => array_sum($levelCounts),
        ]);
    }

    /**
     * Get Team Income Statistics
     */
    public function teamIncomeStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $stats = TeamIncomeRecord::where('recipient_id', $user->id)
            ->select(
                DB::raw('COUNT(*) as total_records'),
                DB::raw('SUM(amount) as total_earned'),
                'level'
            )
            ->groupBy('level')
            ->orderBy('level')
            ->get();

        $totalEarned = TeamIncomeRecord::where('recipient_id', $user->id)->sum('amount');

        return $this->success([
            'by_level' => $stats,
            'total_earned' => $totalEarned,
        ]);
    }
}
