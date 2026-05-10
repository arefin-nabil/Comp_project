<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('full_name', 100);
            $table->char('phone', 11)->unique()->comment('Bangladesh 11-digit format');
            $table->string('password')->comment('Argon2id hashed');
            $table->char('referral_id', 8)->unique()->comment('Immutable after creation');
            $table->unsignedBigInteger('referred_by')->nullable()->comment('Immutable after set');
            $table->enum('status', ['pending', 'active', 'blocked', 'suspended'])->default('pending');
            $table->text('nid')->nullable()->comment('Encrypted NID number');
            $table->text('address')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('two_factor_secret')->nullable()->comment('TOTP secret for admin MFA');
            $table->text('two_factor_recovery_codes')->nullable();
            $table->boolean('two_factor_confirmed')->default(false);
            // Lifetime earnings tracker for club income eligibility
            $table->decimal('total_lifetime_earned', 18, 4)->default(0)->comment('Sum of all income types for club income cap');
            $table->boolean('club_income_eligible')->default(false)->comment('Set true on first club activation, false when cap reached');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();
            $table->index('phone');
            $table->index('referral_id');
            $table->index('referred_by');
            $table->index('status');
            $table->index('club_income_eligible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
