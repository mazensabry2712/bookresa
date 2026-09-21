<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_working_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->foreign(['staff_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('staff_profiles')
                ->cascadeOnDelete();

            $table->unique(['tenant_id', 'staff_id', 'day_of_week']);
            $table->index(['tenant_id', 'staff_id', 'is_closed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_working_hours');
    }
};
