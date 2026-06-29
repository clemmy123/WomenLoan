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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // --- FIXED: Removed ->after('id') from a fresh table creation block ---
            // The ->after() modifier is only valid inside an ALTER/Schema::table migration.
            $table->string('loan_track_id')->unique();
            // --------------------------------------------------

            $table->foreignId('loan_group_id')->nullable()->constrained()->nullOnDelete(); 
            $table->foreignId('applicant_id')->nullable()->constrained()->nullOnDelete(); 

            // Financial Records
            $table->string('requested_amount')->default('0');
            
            // --- NEW ENHANCED WORKFLOW COLUMNS LINKED TO BUSINESS LOGIC ---
            $table->string('proposed_amount')->default('0'); // Set by Ministry Verifier 1 (Step 2)
            $table->string('applicant_acceptance')->default('pending'); // 'pending', 'accepted', 'declined' (Step 3)
            // ---------------------------------------------------------------

            $table->string('disbursed_amount')->default('0');
            $table->string('debt')->default('0');
            $table->string('penalty_fee')->default('0');
            $table->string('paid_amount')->default('0');

            // Default Institutional Context (Women Development Fund under MoCGWSG)
            $table->string('loan_name')->default('WDF');
            $table->string('institute')->default('MoCGWSG');
            
            $table->string('date_issued')->nullable();
            $table->string('status')->default('pending');
            $table->string('loan_type')->nullable();

            $table->integer('current_step')->default(1); // Maps Steps 1 through 8 cleanly
            $table->json('approval_history')->nullable();

            // Banking & Disbursal Records
            $table->string('bank_name')->nullable();
            $table->string('bank_number')->nullable();
            
            // Workflow management context mapping
            // --- FIXED: Explicitly defined constraint before setting nullOnDelete() ---
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_by')->nullable(); 
            $table->text('comments')->nullable();      
          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};