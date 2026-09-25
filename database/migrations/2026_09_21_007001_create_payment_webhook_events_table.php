<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('transaction_id', 128);
            $table->string('status', 32);
            $table->string('event', 32);
            $table->json('payload');
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'provider', 'transaction_id', 'status'],
                'payment_webhook_events_idempotency_unique',
            );
            $table->index(['tenant_id', 'provider', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
