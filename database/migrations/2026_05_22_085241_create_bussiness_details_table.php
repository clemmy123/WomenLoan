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
        Schema::create('business_details', function (Blueprint $table) {
            $table->id();
            
            // Core Application Relational Link
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            
            // Geolocation Relationship Matrix Constraints
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->foreignId('council_id')->constrained('councils')->onDelete('cascade'); // FIXED: Standardized table & key reference
            $table->foreignId('ward_id')->constrained()->onDelete('cascade');
            $table->foreignId('street_id')->nullable()->constrained()->onDelete('set null');
            
            // Profile & Information Metrics
            $table->string('business_name');
            $table->string('business_phone', 20); // Recommended length limit for telephone configurations
            $table->string('business_email')->nullable();
            $table->string('business_sector');
            $table->string('business_type');
            $table->string('tin_number', 20)->nullable(); // Added localized constraint limit length standard
            
            // File Attachment Storage String Paths
            $table->string('proof_address_attachment')->nullable();
            $table->string('business_registration_attachment')->nullable();
            $table->string('business_proposal_document')->nullable(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_details');
    }
};