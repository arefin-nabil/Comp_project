<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\Commerce\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShopperController extends BaseApiController
{
    public function __construct(protected PurchaseService $purchaseService) {}

    /**
     * Search for a customer by phone number (to initiate a transfer)
     */
    public function searchCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $customer = User::where('phone', $request->phone)->first();

        if (!$customer) {
            return $this->error('Customer not found.', 444);
        }

        return $this->success([
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'phone' => $customer->phone,
        ]);
    }

    /**
     * Process a purchase transfer
     */
    public function transfer(Request $request): JsonResponse
    {
        $shopper = $request->user();
        
        if (!$shopper->hasRole('shopper')) {
            return $this->error('Only shoppers can perform transfers.', 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'idempotency_key' => 'required|string|max:128',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $customer = User::find($request->customer_id);

        try {
            $purchase = $this->purchaseService->completePurchase(
                $shopper,
                $customer,
                $request->amount,
                $request->idempotency_key
            );

            return $this->success($purchase, 'Transfer completed successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
