<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_availability', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('staff_id');
            $table->date('available_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->foreign(['staff_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('staff_profiles')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'staff_id', 'available_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_availability');
    }
};
