<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopper_upgrades', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id')->comment('Branch chosen by customer for upgrade');
            $table->enum('payment_method', ['bkash', 'nagad', 'rocket', 'upay', 'offline'])->default('offline');
            $table->decimal('amount', 18, 4)->default(300.0000);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('receipt_number', 80)->nullable();
            $table->string('gateway_transaction_id', 120)->nullable();
            $table->json('gateway_response')->nullable();
            // Payment split tracking (300 tk)
            $table->decimal('referrer_amount', 18, 4)->default(30.0000);
            $table->decimal('own_wallet_amount', 18, 4)->default(30.0000);
            $table->decimal('company_amount', 18, 4)->default(40.0000);
            $table->decimal('onboarding_allocation', 18, 4)->default(200.0000);
            $table->boolean('split_processed')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable()->comment('Branch admin who approved');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->char('idempotency_key', 128)->unique()->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopper_upgrades');
    }
};
