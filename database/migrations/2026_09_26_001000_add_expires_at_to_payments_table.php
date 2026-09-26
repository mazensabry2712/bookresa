<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->index(['tenant_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
