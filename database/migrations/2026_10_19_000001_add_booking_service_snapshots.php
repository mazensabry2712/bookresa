<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->json('service_name_snapshot')->nullable();
            $table->unsignedBigInteger('service_price_minor_snapshot')->nullable();
            $table->char('service_currency_snapshot', 3)->nullable();
            $table->unsignedSmallInteger('service_duration_minutes_snapshot')->nullable();
            $table->unsignedSmallInteger('service_buffer_minutes_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'service_name_snapshot',
                'service_price_minor_snapshot',
                'service_currency_snapshot',
                'service_duration_minutes_snapshot',
                'service_buffer_minutes_snapshot',
            ]);
        });
    }
};
