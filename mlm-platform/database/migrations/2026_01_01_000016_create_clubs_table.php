<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('club_number')->unique()->comment('Global sequential, assigned atomically via Redis lock');
            $table->decimal('points_consumed', 12, 4)->default(100.0000)->comment('Always exactly 100');
            $table->decimal('points_before', 12, 4)->comment('User point balance before activation');
            $table->decimal('points_after', 12, 4)->comment('User point balance after activation (remainder)');
            $table->enum('status', ['active', 'income_stopped'])->default('active');
            $table->boolean('income_eligible')->default(true)->comment('Set false when lifetime earnings cap reached');
            $table->unsignedTinyInteger('bonus_paid_count')->default(0)->comment('How many of 8 bonuses have been paid (0-8)');
            $table->timestamp('activated_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id');
            $table->index('club_number');
            $table->index(['income_eligible', 'status']);
            $table->index('bonus_paid_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
