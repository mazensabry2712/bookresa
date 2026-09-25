<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'holiday_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_holidays');
    }
};
