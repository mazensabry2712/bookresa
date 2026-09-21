<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('enabled')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'module_id']);
            $table->index(['tenant_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
