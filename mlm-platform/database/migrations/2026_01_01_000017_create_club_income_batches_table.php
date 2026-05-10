<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_income_batches', function (Blueprint $table) {
            $table->id();
            $table->date('batch_date')->unique()->comment('The settlement date (nightly)');
            $table->decimal('total_points_today', 12, 4)->comment('Sum of all points earned that day');
            $table->decimal('club_income_rate', 8, 4)->comment('BDT per point (e.g. 0.4000)');
            $table->decimal('club_pool', 18, 4)->comment('total_points_today * club_income_rate');
            $table->unsignedInteger('eligible_member_count')->default(0);
            $table->decimal('per_member_amount', 18, 4)->comment('club_pool / eligible_member_count');
            $table->decimal('total_distributed', 18, 4)->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->char('idempotency_key', 128)->unique();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'batch_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_income_batches');
    }
};
