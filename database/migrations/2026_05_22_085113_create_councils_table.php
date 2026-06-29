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
        // CHANGED: Table renamed to 'councils' to align perfectly with Council::class
        Schema::create('councils', function (Blueprint $table) {
            $table->id();
            
            // Connects this council up to its parent district
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // CHANGED: Drops the correct table if rolled back
        Schema::dropIfExists('councils');
    }
};