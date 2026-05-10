<?php

namespace App\Http\Controllers\Api;

use App\Models\Branch;
use App\Models\BranchFunding;
use App\Models\ShopperFunding;
use App\Models\User;
use App\Services\Branch\ShopperFundingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchManagerController extends BaseApiController
{
    public function __construct(protected ShopperFundingService $shopperFundingService) {}

    /**
     * Get branch stats for the manager
     */
    public function stats(Request $request): JsonResponse
    {
        $branch = Branch::where('manager_id', $request->user()->id)->first();
        
        if (!$branch) {
            return $this->error('You are not a branch manager.', 403);
        }

        return $this->success([
            'branch' => $branch->load('wallet'),
            'total_received_from_admin' => BranchFunding::where('branch_id', $branch->id)->sum('amount'),
            'total_distributed_to_shoppers' => ShopperFunding::where('branch_id', $branch->id)->sum('amount'),
        ]);
    }

    /**
     * Distribute funds to a shopper
     */
    public function fundShopper(Request $request): JsonResponse
    {
        $branch = Branch::where('manager_id', $request->user()->id)->first();
        
        if (!$branch) {
            return $this->error('Unauthorized.', 403);
        }

        $validator = Validator::make($request->all(), [
            'shopper_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:100',
            'idempotency_key' => 'required|string|max:128',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $shopper = User::find($request->shopper_id);

        try {
            $funding = $this->shopperFundingService->fund(
                $branch,
                $shopper,
                $request->amount,
                $request->idempotency_key,
                $request->notes
            );

            return $this->success($funding, 'Funds transferred to shopper.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
