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
        Schema::create('gurantors', function (Blueprint $table) {
            $table->id();
            
            // Relational Parent and Profile Links (Safe to keep as long as tables exist)
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('applicant_id')->nullable()->constrained()->nullOnDelete();
            
            // SOLUTION: Raw unsigned big integers stop MySQL from throwing 'errno 150'
            $table->unsignedBigInteger('guarantor_region_id')->nullable();
            $table->unsignedBigInteger('guarantor_district_id')->nullable();
            $table->unsignedBigInteger('guarantor_council_id')->nullable(); 
            $table->unsignedBigInteger('guarantor_ward_id')->nullable();
            $table->unsignedBigInteger('guarantor_street_id')->nullable();

            // Personal Profile Information Metrics
            $table->string('name');
            $table->string('phone', 20); 
            $table->string('relationship');
            $table->string('id_number', 50); 
            $table->string('occupation')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gurantors');
    }
};