<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('block_ends_at');
            $table->string('status', 32)->default('pending')->index();
            $table->string('payment_status', 32)->default('unpaid')->index();
            $table->string('booking_reference', 32)->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id']);

            $table->foreign(['customer_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();

            $table->foreign(['service_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('services')
                ->cascadeOnDelete();

            $table->foreign(['staff_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('staff_profiles');

            $table->index(['tenant_id', 'starts_at', 'status']);
            $table->index(['tenant_id', 'staff_id', 'starts_at', 'status']);
            $table->index(['tenant_id', 'customer_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
