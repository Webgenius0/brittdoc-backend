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
        Schema::create('venues', function (Blueprint $table) {
            // $table->id();
            // $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // $table->string('name')->nullable();
            // $table->foreignId('category_id')->constrained()->onDelete('cascade');
            // $table->text('description')->nullable();
            // $table->string('location')->nullable();
            // $table->integer('capacity')->nullable();
            // $table->decimal('price', 10, 2)->nullable();
            // $table->date('start_date')->nullable();
            // $table->date('ending_date')->nullable();
            // $table->time('available_start_time')->nullable();
            // $table->time('available_end_time')->nullable();
            // $table->json('image')->nullable();
            // //latitude and longitude
            // $table->decimal('latitude', 10, 8)->nullable();
            // $table->decimal('longitude', 11, 8)->nullable();
            // $table->enum('status', ['active', 'inactive'])->default('active');
            // $table->timestamps();
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->date('start_date')->nullable();
            $table->date('ending_date')->nullable();
            $table->json('image')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
