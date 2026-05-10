<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->char('key', 128)->unique();
            $table->string('action', 80)->comment('e.g. wallet_credit, withdrawal, payment_verify');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('result_reference_type', 80)->nullable();
            $table->unsignedBigInteger('result_reference_id')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('NULL = never expires');
            $table->timestamps();

            $table->index(['actor_id', 'action']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
