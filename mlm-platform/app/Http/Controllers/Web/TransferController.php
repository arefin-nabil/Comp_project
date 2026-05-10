<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Commerce\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    public function __construct(protected PurchaseService $purchaseService) {}

    public function index()
    {
        return view('shopper.transfer');
    }

    public function checkCustomer(Request $request)
    {
        $customer = User::where('phone', $request->phone)->first();
        if ($customer) {
            return response()->json(['success' => true, 'data' => $customer]);
        }
        return response()->json(['success' => false], 444);
    }

    public function process(Request $request)
    {
        $request->validate([
            'phone' => 'required|exists:users,phone',
            'amount' => 'required|numeric|min:1',
            'idempotency_key' => 'required|string',
        ]);

        $shopper = $request->user();
        $customer = User::where('phone', $request->phone)->first();

        try {
            $this->purchaseService->completePurchase(
                $shopper,
                $customer,
                $request->amount,
                $request->idempotency_key
            );

            return redirect()->route('dashboard')->with('success', 'Transfer completed! MLM rewards distributed.');
        } catch (\Exception $e) {
            return back()->withErrors([$e->getMessage()]);
        }
    }
}
