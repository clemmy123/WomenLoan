<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This creates the physical table inside MySQL.
     */
    public function up(): void
    {
        Schema::create('draft_loans', function (Blueprint $table) {
            $table->id();
            
            // Stores the ID of the authenticated user who is filling out the form
            $table->unsignedBigInteger('user_id'); 
            
            // Stores the unique tracking code (e.g., WL0000001) for search/resume tracking
            $table->string('track_id')->unique();
            
            // Holds the incomplete form states as a clean JSON snapshot
            $table->json('form_data'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * This drops the table if you rollback your migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_loans');
    }
};
