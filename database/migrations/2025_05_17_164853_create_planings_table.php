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
        Schema::create('planings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['lifetime', 'monthly']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('stripe_price_id')->nullable(); // Stripe Price ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planings');
    }
};
