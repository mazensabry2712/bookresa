<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->dropUnique('payment_webhook_events_idempotency_unique');
            $table->unique(
                ['tenant_id', 'provider', 'transaction_id', 'event', 'status'],
                'payment_webhook_events_event_idempotency_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->dropUnique('payment_webhook_events_event_idempotency_unique');
            $table->unique(
                ['tenant_id', 'provider', 'transaction_id', 'status'],
                'payment_webhook_events_idempotency_unique',
            );
        });
    }
};
