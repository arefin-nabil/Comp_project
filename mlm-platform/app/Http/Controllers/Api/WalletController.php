<?php

namespace App\Http\Controllers\Api;

use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends BaseApiController
{
    /**
     * Get Wallet Balance
     */
    public function balance(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;
        return $this->success([
            'balance' => $wallet->balance,
            'points_balance' => $wallet->points_balance,
            'frozen_balance' => $wallet->frozen_balance,
        ]);
    }

    /**
     * Get Ledger (Transaction History)
     */
    public function transactions(Request $request): JsonResponse
    {
        $walletId = $request->user()->wallet->id;
        
        $transactions = WalletTransaction::where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($transactions);
    }
}
