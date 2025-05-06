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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('general_notification')->default(true);       
            $table->boolean('sound')->default(true);      
            $table->boolean('vibration')->default(true);      
            $table->boolean('special_offer')->default(true);  
            $table->boolean('payment')->default(true);     
            $table->boolean('app_update')->default(true); 
            $table->boolean('other')->default(true);       
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
