<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable snapshot of referral chain at the time of registration
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->comment('The referred user');
            $table->unsignedBigInteger('referrer_id')->comment('Direct referrer (level 1)');
            // Pre-computed ancestor chain for fast 10-level team income (stored once at registration)
            $table->unsignedBigInteger('level2_id')->nullable();
            $table->unsignedBigInteger('level3_id')->nullable();
            $table->unsignedBigInteger('level4_id')->nullable();
            $table->unsignedBigInteger('level5_id')->nullable();
            $table->unsignedBigInteger('level6_id')->nullable();
            $table->unsignedBigInteger('level7_id')->nullable();
            $table->unsignedBigInteger('level8_id')->nullable();
            $table->unsignedBigInteger('level9_id')->nullable();
            $table->unsignedBigInteger('level10_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — immutable

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('referrer_id')->references('id')->on('users');
            $table->index('referrer_id');
            $table->index('level2_id');
            $table->index('level3_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
