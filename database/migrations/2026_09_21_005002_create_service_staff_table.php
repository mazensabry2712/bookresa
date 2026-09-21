<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('staff_id');
            $table->timestamps();

            $table->foreign(['service_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('services')
                ->cascadeOnDelete();

            $table->foreign(['staff_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('staff_profiles')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'service_id', 'staff_id']);
            $table->index(['tenant_id', 'staff_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_staff');
    }
};
