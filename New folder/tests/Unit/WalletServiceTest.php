<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);
    }

    public function test_it_can_credit_a_wallet_correctly()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'balance' => '100.0000',
        ]);

        $idempotencyKey = (string) Str::uuid();
        
        $txn = $this->walletService->credit(
            wallet: $wallet,
            amount: '50.5000',
            category: 'adjustment',
            referenceType: User::class,
            referenceId: $user->id,
            idempotencyKey: $idempotencyKey,
            description: 'Test Credit'
        );

        $this->assertEquals(0, bccomp('150.5000', $wallet->refresh()->balance, 4));
        $this->assertEquals(0, bccomp('100.0000', $txn->balance_before, 4));
        $this->assertEquals(0, bccomp('150.5000', $txn->balance_after, 4));
        $this->assertEquals('credit', $txn->type);
    }

    public function test_it_prevents_insufficient_balance_debit()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'balance' => '10.0000',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance.');

        $this->walletService->debit(
            wallet: $wallet,
            amount: '20.0000',
            category: 'purchase',
            referenceType: User::class,
            referenceId: $user->id,
            idempotencyKey: (string) Str::uuid()
        );
    }

    public function test_it_enforces_idempotency_on_transactions()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'balance' => '100.0000',
        ]);

        $idempotencyKey = 'shared_key_123';

        $this->walletService->credit($wallet, '10.0000', 'adjustment', User::class, $user->id, $idempotencyKey);
        
        // Second call with same key should return existing txn and NOT add balance again
        $txn = $this->walletService->credit($wallet, '10.0000', 'adjustment', User::class, $user->id, $idempotencyKey);

        $this->assertEquals(0, bccomp('110.0000', $wallet->refresh()->balance, 4));
        $this->assertCount(1, WalletTransaction::where('wallet_id', $wallet->id)->get());
    }
}
