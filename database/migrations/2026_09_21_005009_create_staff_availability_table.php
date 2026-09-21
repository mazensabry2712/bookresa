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
            $table->foreignId('staff_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->date('available_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->index(['staff_id', 'available_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_availability');
    }
};
