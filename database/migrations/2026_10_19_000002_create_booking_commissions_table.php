<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('customer_ordinal');
            $table->unsignedBigInteger('included_customer_limit');
            $table->boolean('eligible')->default(false);
            $table->unsignedInteger('rate_basis_points')->default(0);
            $table->unsignedBigInteger('gross_amount_minor');
            $table->unsignedBigInteger('refunded_amount_minor')->default(0);
            $table->unsignedBigInteger('commission_amount_minor')->default(0);
            $table->unsignedBigInteger('workspace_net_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 20)->default('accrued');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'currency', 'status'], 'booking_commissions_balance_index');
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_commissions');
    }
};
