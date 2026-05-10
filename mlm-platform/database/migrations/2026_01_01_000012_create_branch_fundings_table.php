<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_fundings', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('funded_by')->comment('Finance admin user id');
            $table->decimal('base_amount', 18, 4)->comment('Amount sent by company (e.g. 1000)');
            $table->decimal('incentive_rate', 8, 4)->default(3.0000)->comment('% incentive e.g. 3%');
            $table->decimal('incentive_amount', 18, 4)->comment('Calculated incentive (e.g. 30)');
            $table->decimal('total_credited', 18, 4)->comment('base + incentive (e.g. 1030)');
            $table->char('idempotency_key', 128)->unique();
            $table->unsignedBigInteger('debit_transaction_id')->nullable()->comment('Company wallet debit txn');
            $table->unsignedBigInteger('credit_transaction_id')->nullable()->comment('Branch wallet credit txn');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('funded_by')->references('id')->on('users');
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_fundings');
    }
};
