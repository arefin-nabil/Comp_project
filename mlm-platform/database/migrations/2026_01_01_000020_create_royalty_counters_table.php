<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedInteger('total_direct_clubs')->default(0)->comment('Total clubs from direct referrals lifetime');
            $table->unsignedInteger('last_paid_at_count')->default(0)->comment('The direct club count when last payout happened');
            $table->decimal('total_royalty_earned', 18, 4)->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['total_direct_clubs', 'last_paid_at_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_counters');
    }
};
