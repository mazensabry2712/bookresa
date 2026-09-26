<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'created_at']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('reminder_sent_at')->nullable();
            $table->index(['tenant_id', 'starts_at', 'status', 'reminder_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'starts_at', 'status', 'reminder_sent_at']);
            $table->dropColumn('reminder_sent_at');
        });

        Schema::dropIfExists('notifications');
    }
};
