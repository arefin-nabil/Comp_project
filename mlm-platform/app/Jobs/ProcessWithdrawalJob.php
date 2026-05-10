<?php

namespace App\Jobs;

use App\Models\Withdrawal;
use App\Models\User;
use App\Services\Withdrawal\WithdrawalService;
use App\Events\WithdrawalPaid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWithdrawalJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 60, 300, 900, 3600];

    public function __construct(
        public Withdrawal $withdrawal,
        public User $approver,
        public ?string $gatewayTxnId = null,
        public ?string $notes = null
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->withdrawal->id;
    }

    public function handle(WithdrawalService $withdrawalService): void
    {
        if ($this->withdrawal->status !== 'pending') {
            Log::warning("Skipping ProcessWithdrawalJob: withdrawal {$this->withdrawal->id} is not pending.");
            return;
        }

        try {
            $withdrawalService->approveAndPay(
                $this->withdrawal,
                $this->approver,
                $this->gatewayTxnId,
                $this->notes
            );
            
            event(new WithdrawalPaid($this->withdrawal));
        } catch (\Exception $e) {
            Log::error("ProcessWithdrawalJob failed for {$this->withdrawal->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
