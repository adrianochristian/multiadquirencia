<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subacquirer_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->string('withdrawal_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('PENDING');
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('account_type')->nullable();
            $table->string('document')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('raw_request')->nullable();
            $table->text('raw_response')->nullable();
            $table->text('webhook_payload')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
