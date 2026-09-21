<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->morphs('payable');
            $table->string('reference', 32)->unique();
            $table->string('provider', 32);
            $table->string('provider_reference', 128)->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 32)->default('pending');
            $table->string('method', 32)->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'payable_type', 'payable_id']);
            $table->unique(
                ['tenant_id', 'provider', 'idempotency_key'],
                'payments_tenant_provider_idempotency_unique',
            );
            $table->unique(
                ['provider', 'provider_reference'],
                'payments_provider_reference_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
