<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_payments', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('method', ['bkash', 'nagad', 'rocket', 'upay', 'offline'])->default('offline');
            $table->decimal('amount', 18, 4)->default(100.0000);
            $table->enum('status', ['pending', 'verified', 'rejected', 'refunded'])->default('pending');
            // Offline payment fields
            $table->string('receipt_number', 80)->nullable();
            $table->unsignedBigInteger('verified_by')->nullable()->comment('Admin user id');
            $table->timestamp('verified_at')->nullable();
            // Online payment fields
            $table->string('gateway_transaction_id', 120)->nullable();
            $table->json('gateway_response')->nullable();
            // Split record
            $table->decimal('referrer_amount', 18, 4)->default(30.0000);
            $table->decimal('company_amount', 18, 4)->default(70.0000);
            $table->boolean('split_processed')->default(false);
            $table->char('idempotency_key', 128)->unique()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index('receipt_number');
            $table->index('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_payments');
    }
};
