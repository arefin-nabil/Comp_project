<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('branch_name', 100);
            $table->string('division', 60);
            $table->string('district', 60);
            $table->string('upazila', 60);
            $table->text('address')->nullable();
            $table->string('contact_phone', 11)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('managed_by')->nullable()->comment('branch_admin user id');
            $table->decimal('wallet_balance', 18, 4)->default(0)->comment('Snapshot; source of truth in wallet_transactions');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('managed_by')->references('id')->on('users')->nullOnDelete();
            $table->index('division');
            $table->index('district');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
