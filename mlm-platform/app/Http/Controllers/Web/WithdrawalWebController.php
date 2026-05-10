<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalWebController extends Controller
{
    public function __construct(protected WithdrawalService $withdrawalService) {}

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:500',
            'method' => 'required|in:bkash,nagad,rocket,bank',
            'account_details' => 'required|string',
            'idempotency_key' => 'required|string',
        ]);

        try {
            $this->withdrawalService->requestWithdrawal(
                $request->user(),
                $request->amount,
                $request->method,
                $request->account_details,
                $request->idempotency_key
            );

            return back()->with('success', 'Withdrawal request submitted.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }
}
