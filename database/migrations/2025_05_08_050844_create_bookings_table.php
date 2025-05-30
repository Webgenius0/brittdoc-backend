<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('location')->nullable();
            $table->string('name')->nullable();
            $table->date('booking_date')->nullable();
            $table->time('booking_start_time')->nullable();
            $table->time('booking_end_time')->nullable();
            $table->boolean('custom_Booking')->default(false);
            $table->decimal('platform_rate', 10, 2)->nullable();
            $table->decimal('fee_percentage', 5, 2)->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->string('info')->default('Paid 24h ofter event completion');
            $table->enum('status', ['pending', 'booked', 'upcoming', 'in-progress', 'withdraw', 'accept', 'request', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
