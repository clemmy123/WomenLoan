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
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            
            // FIXED: Pointing directly to your clean councils table using council_id
            $table->foreignId('council_id')->constrained('councils')->onDelete('cascade');
            
            // Excellent choice keeping this for fast direct administrative filtering
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
        Schema::dropIfExists('wards');
    }
};