<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('reference', 32)->unique();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('status', 20)->default('pending');
            $table->string('payout_method', 80)->nullable();
            $table->string('payout_reference', 128)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'currency', 'status'], 'workspace_payouts_balance_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_payouts');
    }
};
