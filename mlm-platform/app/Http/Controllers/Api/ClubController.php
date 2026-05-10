<?php

namespace App\Http\Controllers\Api;

use App\Models\Club;
use App\Models\ClubIncomeDistribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClubController extends BaseApiController
{
    /**
     * Get user's active clubs and sequential history
     */
    public function index(Request $request): JsonResponse
    {
        $clubs = Club::where('user_id', $request->user()->id)
            ->orderBy('club_number', 'asc')
            ->get();

        return $this->success($clubs);
    }

    /**
     * Get club income history
     */
    public function incomeHistory(Request $request): JsonResponse
    {
        $history = ClubIncomeDistribution::where('user_id', $request->user()->id)
            ->with('batch')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($history);
    }

    /**
     * Get global club statistics (for transparency)
     */
    public function globalStats(): JsonResponse
    {
        $totalClubs = Club::count();
        $totalEligible = Club::where('status', 'active')->where('income_eligible', true)->count();
        $lastSettlement = DB::table('club_income_batches')->latest()->first();

        return $this->success([
            'total_global_clubs' => $totalClubs,
            'eligible_active_clubs' => $totalEligible,
            'last_settlement_date' => $lastSettlement?->settlement_date,
            'last_settlement_pool' => $lastSettlement?->total_pool_amount,
        ]);
    }
}
