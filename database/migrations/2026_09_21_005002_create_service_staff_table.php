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
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_id', 'staff_id']);
            $table->index(['staff_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_staff');
    }
};
