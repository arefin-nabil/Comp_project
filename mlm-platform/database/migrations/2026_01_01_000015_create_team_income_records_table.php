<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_income_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->comment('Source purchase');
            $table->unsignedBigInteger('recipient_id')->nullable()->comment('User receiving the income (null if company fund)');
            $table->unsignedBigInteger('source_user_id')->comment('User who made the purchase (customer)');
            $table->unsignedTinyInteger('level')->comment('1-10 MLM level');
            $table->decimal('rate', 8, 4)->comment('e.g. 10.00, 6.00 … 1.00');
            $table->decimal('points_value', 18, 4)->comment('Base points_value from purchase');
            $table->decimal('amount', 18, 4)->comment('Actual amount credited (rate% of points_value)');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->boolean('is_company_fund')->default(false)->comment('True if upline was missing (unallocated goes to company)');
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases');
            $table->foreign('recipient_id')->references('id')->on('users');
            $table->index(['recipient_id', 'created_at']);
            $table->index(['purchase_id', 'level']);
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_income_records');
    }
};
