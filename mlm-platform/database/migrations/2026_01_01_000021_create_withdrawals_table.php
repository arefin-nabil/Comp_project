<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['online', 'branch']);
            $table->unsignedBigInteger('branch_id')->nullable()->comment('Required if type=branch');
            $table->decimal('requested_amount', 18, 4);
            $table->decimal('vat_amount', 18, 4)->comment('5% online, 7% branch');
            $table->decimal('payable_amount', 18, 4)->comment('requested - vat');
            $table->decimal('branch_split_amount', 18, 4)->default(0)->comment('3% for branch if type=branch');
            $table->decimal('company_split_amount', 18, 4)->default(0)->comment('Remaining vat split');
            $table->string('gateway', 30)->nullable()->comment('bkash, nagad, rocket, upay');
            $table->string('account_number', 30)->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->boolean('otp_verified')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable()->comment('Finance admin user id');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->comment('The user wallet debit txn');
            $table->string('gateway_transaction_id', 120)->nullable();
            $table->text('notes')->nullable();
            $table->char('idempotency_key', 128)->unique()->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
