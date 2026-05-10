<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_income_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('club_id')->comment('The qualifying club record');
            $table->decimal('amount', 18, 4);
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->enum('status', ['pending', 'credited', 'failed'])->default('pending');
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('club_income_batches');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('club_id')->references('id')->on('clubs');
            $table->unique(['batch_id', 'user_id'])->comment('One distribution per user per batch');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_income_distributions');
    }
};
