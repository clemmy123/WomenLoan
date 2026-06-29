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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('nin', 20)->unique(); 
            
            // NIDA Name Breakdown Metrics
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name'); 
            
            $table->date('dob'); 
            
            // Demographic Data
            $table->string('sex', 10)->nullable(); 
            $table->string('marital_status', 20)->nullable(); 
            $table->string('nationality')->default('Tanzanian');
            
            $table->string('phone', 15);
            $table->string('email')->nullable();
            
            // Biometrics
            $table->text('photo_path')->nullable();
            $table->text('signature_path')->nullable();
            
            // NIDA Compliance Flags
            $table->boolean('nida_verified')->default(false);
            $table->timestamp('nida_verified_at')->nullable();
            $table->date('issuer_date')->nullable(); 
            
            // Binds location safely using the native constrained lookup wrapper
            $table->foreignId('location_id')
                  ->nullable()
                  ->constrained('locations')
                  ->nullOnDelete();
            
            $table->string('attachment')->nullable();
            
            // System Core Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('loan_id')->nullable(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};