<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_bonus_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedTinyInteger('bonus_number')->comment('1-8');
            $table->decimal('amount', 18, 4);
            $table->unsignedBigInteger('trigger_club_id')->comment('The club whose activation triggered this bonus');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->enum('status', ['pending', 'credited', 'failed'])->default('pending');
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs');
            $table->foreign('trigger_club_id')->references('id')->on('clubs');
            $table->unique(['club_id', 'bonus_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_bonus_payouts');
    }
};
