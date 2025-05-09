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
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->string('location');
            $table->string('name');
            $table->string('image')->nullable();
            $table->date('booking_date');
            $table->time('booking_start_time');
            $table->time('booking_end_time');
            $table->time('remaining_time');
            //price
            $table->decimal('platform_rate', 10, 2);
            $table->decimal('fee_percentage', 5, 2);
            $table->decimal('fee_amount', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->string('info')->default('Paid 24h ofter event completion');
            $table->enum('status', ['pending','upcoming', 'in-progress', 'completed', 'cancelled'])->default('pending');
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
