<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('usage_period_id')->constrained('usage_periods')->cascadeOnDelete();
            $table->string('type', 64);
            $table->unsignedBigInteger('units');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('amount_minor');
            $table->json('pricing_snapshot');
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->unique(['usage_period_id', 'type'], 'usage_charges_period_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_charges');
    }
};
