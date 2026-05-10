<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            // Polymorphic owner: user or branch
            $table->morphs('owner'); // owner_type, owner_id
            $table->decimal('balance', 18, 4)->default(0)->comment('Projected snapshot — source of truth is wallet_transactions');
            $table->decimal('points_balance', 12, 4)->default(0)->comment('MLM points (not withdrawable directly)');
            $table->decimal('frozen_balance', 18, 4)->default(0)->comment('Amount locked for pending withdrawals');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
            $table->index('balance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
