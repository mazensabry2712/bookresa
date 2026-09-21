<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('display_name', 120);
            $table->string('phone', 40)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id']);
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
