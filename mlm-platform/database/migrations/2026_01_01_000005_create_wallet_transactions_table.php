<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique()->comment('External-facing immutable ID');
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 18, 4)->unsigned();
            $table->decimal('balance_before', 18, 4)->comment('Snapshot before this transaction');
            $table->decimal('balance_after', 18, 4)->comment('Snapshot after this transaction');
            $table->string('category', 60)->comment('cashback|team_income|club_income|club_bonus|royalty|purchase|withdrawal|branch_fund|shopper_fund|registration|shopper_upgrade|refund|adjustment');
            $table->string('reference_type', 80)->nullable()->comment('Polymorphic source model');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->char('idempotency_key', 128)->unique()->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Intentionally NO updated_at — this is an immutable ledger

            $table->foreign('wallet_id')->references('id')->on('wallets');
            $table->index(['wallet_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('category');
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
