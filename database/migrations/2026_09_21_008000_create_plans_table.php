<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->string('billing_period', 32);
            $table->unsignedBigInteger('included_customer_limit')->default(0);
            $table->unsignedBigInteger('additional_customer_price_minor')->default(0);
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_modules');
        Schema::dropIfExists('plans');
    }
};
