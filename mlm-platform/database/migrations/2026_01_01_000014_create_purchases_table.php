<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('shopper_id');
            $table->unsignedBigInteger('customer_id');
            $table->decimal('transfer_amount', 18, 4)->comment('Amount shopper transfers to customer');
            // Split: 40% cashback (withdrawable), 60% points
            $table->decimal('cashback_rate', 8, 4)->default(40.0000)->comment('% e.g. 40%');
            $table->decimal('cashback_amount', 18, 4)->comment('Withdrawable BDT (40% of transfer)');
            $table->decimal('points_rate', 8, 4)->default(60.0000)->comment('% e.g. 60%');
            $table->decimal('points_value', 18, 4)->comment('BDT value going to points (60% of transfer)');
            $table->decimal('points_awarded', 12, 4)->comment('Points = points_value / 6 (1pt = 6tk)');
            // Team income tracking
            $table->decimal('team_income_pool', 18, 4)->comment('35% of points_value for 10-level distribution');
            $table->boolean('team_income_distributed')->default(false);
            $table->timestamp('team_income_distributed_at')->nullable();
            $table->char('idempotency_key', 128)->unique();
            $table->enum('status', ['completed', 'reversed'])->default('completed');
            $table->timestamps();

            $table->foreign('shopper_id')->references('id')->on('users');
            $table->foreign('customer_id')->references('id')->on('users');
            $table->index(['shopper_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index('team_income_distributed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
