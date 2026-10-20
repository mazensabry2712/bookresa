<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_commission_period_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->date('period_start');
            $table->unsignedBigInteger('next_ordinal')->default(1);
            $table->timestamps();

            $table->unique(['tenant_id', 'period_start'], 'booking_commission_period_counter_unique');
        });

        Schema::create('booking_commission_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->unsignedBigInteger('booking_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('timezone', 64);
            $table->unsignedBigInteger('booking_ordinal');
            $table->unsignedBigInteger('free_booking_allowance');
            $table->boolean('commission_enabled')->default(false);
            $table->boolean('eligible')->default(false);
            $table->unsignedInteger('rate_basis_points')->default(0);
            $table->unsignedBigInteger('booking_amount_minor_snapshot')->default(0);
            $table->unsignedBigInteger('commission_amount_minor_snapshot')->default(0);
            $table->char('currency', 3);
            $table->string('policy_version', 40)->default('monthly-booking-v1');
            $table->timestamp('qualified_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign(['booking_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('bookings')
                ->restrictOnDelete();

            $table->unique('booking_id', 'booking_commission_allocation_booking_unique');
            $table->unique(
                ['tenant_id', 'period_start', 'booking_ordinal'],
                'booking_commission_allocation_ordinal_unique',
            );
            $table->index(
                ['tenant_id', 'period_start', 'eligible'],
                'booking_commission_allocation_month_index',
            );
        });

        Schema::table('booking_commissions', function (Blueprint $table): void {
            $table->foreignId('booking_commission_allocation_id')
                ->nullable()
                ->constrained('booking_commission_allocations')
                ->restrictOnDelete();
            $table->unsignedBigInteger('booking_ordinal')->nullable();
            $table->unsignedBigInteger('free_booking_allowance')->nullable();
            $table->string('commission_policy_version', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('booking_commissions', function (Blueprint $table): void {
            $table->dropForeign(['booking_commission_allocation_id']);
            $table->dropColumn([
                'booking_commission_allocation_id',
                'booking_ordinal',
                'free_booking_allowance',
                'commission_policy_version',
            ]);
        });

        Schema::dropIfExists('booking_commission_allocations');
        Schema::dropIfExists('booking_commission_period_counters');
    }
};
