<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This setup creates the main structural records for groups and enforces pivot indexing.
     */
    public function up(): void
    {
        // 1. Core Groups Enterprise Entity Table
        Schema::create('loan_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            
            // --- NEW ENHANCED FIELDS ADDED HERE ---
            $table->string('registration_number')->nullable()->unique(); // Government registration tracking handle
            $table->string('phone')->nullable();                         // Primary group contact line
            $table->string('email')->nullable();                         // Optional group digital contact
            // --------------------------------------

            $table->timestamps();
        });

        // 2. Many-To-Many Relational Intersection Bridge (Pivot Table)
        // This links the 'applicants' table to the 'loan_groups' table seamlessly
        Schema::create('applicant_loan_group', function (Blueprint $table) {
            $table->id();
            
            // Binds to individual user/profile data record
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            
            // Binds to the designated target group identity
            $table->foreignId('loan_group_id')->constrained()->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * Drops the associative links first to avoid breaking database engine foreign constraint guards.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_loan_group');
        Schema::dropIfExists('loan_groups');
    }
};