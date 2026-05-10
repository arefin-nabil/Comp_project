<?php

namespace App\Http\Controllers\Api;

use App\Models\Withdrawal;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WithdrawalController extends BaseApiController
{
    public function __construct(protected WithdrawalService $withdrawalService) {}

    /**
     * List user withdrawals
     */
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($withdrawals);
    }

    /**
     * Submit a withdrawal request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:500', // Minimum 500 BDT
            'method' => 'required|in:bkash,nagad,rocket,bank',
            'account_details' => 'required|string',
            'idempotency_key' => 'required|string|max:128',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                $request->user(),
                $request->amount,
                $request->method,
                $request->account_details,
                $request->idempotency_key
            );

            return $this->success($withdrawal, 'Withdrawal request submitted.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
