<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_working_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'work_date']);
            $table->index(['tenant_id', 'work_date', 'is_closed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_working_hours');
    }
};
