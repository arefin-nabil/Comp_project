<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopper_fundings', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('shopper_id')->comment('Shopper user id');
            $table->unsignedBigInteger('branch_id')->comment('Funding branch');
            $table->unsignedBigInteger('funded_by')->comment('Branch admin user id');
            $table->decimal('base_amount', 18, 4)->comment('Amount sent by branch (e.g. 1000)');
            $table->decimal('incentive_rate', 8, 4)->default(2.0000)->comment('% incentive e.g. 2%');
            $table->decimal('incentive_amount', 18, 4)->comment('Calculated incentive (e.g. 20)');
            $table->decimal('total_credited', 18, 4)->comment('base + incentive (e.g. 1020)');
            $table->char('idempotency_key', 128)->unique();
            $table->unsignedBigInteger('branch_debit_transaction_id')->nullable();
            $table->unsignedBigInteger('shopper_credit_transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('shopper_id')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('funded_by')->references('id')->on('users');
            $table->index(['shopper_id', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopper_fundings');
    }
};
