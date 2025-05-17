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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planing_id');
            $table->foreign('planing_id')->references('id')->on('planings')->cascadeOnDelete();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('venues')->onDelete('cascade');

            $table->enum('type', ['lifetime', 'monthly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['pending', 'active', 'inactive', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};
