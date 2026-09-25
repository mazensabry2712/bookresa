<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('status', 32);
            $table->string('payment_status', 32)->default('pending');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->string('billing_period', 32);
            $table->unsignedBigInteger('included_customer_limit');
            $table->unsignedBigInteger('additional_customer_price_minor');
            $table->json('pricing_snapshot');
            $table->foreignId('next_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamp('plan_change_effective_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'start_at', 'end_at'], 'subscriptions_tenant_period_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
