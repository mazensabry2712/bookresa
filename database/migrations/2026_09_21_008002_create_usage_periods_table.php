<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedBigInteger('unique_customer_count');
            $table->unsignedBigInteger('included_customer_limit');
            $table->unsignedBigInteger('additional_customer_count');
            $table->unsignedBigInteger('additional_customer_price_minor');
            $table->unsignedBigInteger('base_price_minor');
            $table->unsignedBigInteger('usage_charge_minor');
            $table->unsignedBigInteger('total_charge_minor');
            $table->char('currency', 3);
            $table->string('status', 32)->default('open');
            $table->json('pricing_snapshot');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'subscription_id', 'period_start', 'period_end'],
                'usage_periods_subscription_window_unique',
            );
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_periods');
    }
};
