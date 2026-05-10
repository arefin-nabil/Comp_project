<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->decimal('computed_balance', 18, 4)->comment('SUM(credit) - SUM(debit) from ledger');
            $table->decimal('snapshot_balance', 18, 4)->comment('wallets.balance at snapshot time');
            $table->boolean('is_match')->default(true);
            $table->text('discrepancy_notes')->nullable();
            $table->unsignedBigInteger('last_transaction_id')->nullable()->comment('Highest txn ID included in this snapshot');
            $table->timestamp('snapshotted_at')->useCurrent();

            $table->foreign('wallet_id')->references('id')->on('wallets');
            $table->index(['wallet_id', 'snapshotted_at']);
            $table->index('is_match');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_snapshots');
    }
};
