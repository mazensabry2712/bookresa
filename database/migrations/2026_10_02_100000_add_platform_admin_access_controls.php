<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->string('role', 64)->default('super_admin')->after('is_active')->index();
            $table->json('permissions')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn(['permissions', 'role']);
        });
    }
};
