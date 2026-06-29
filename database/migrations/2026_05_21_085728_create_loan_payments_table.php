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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            
            // Financial Ledger Matrices (Using uniform highly precise decimals)
            $table->decimal('amount_requested', 20, 2)->nullable(); // Safe fallback formatting
            $table->decimal('amount_disbursed', 20, 2)->nullable();
            
            // --- CRITICAL ADDITION BELOW ---
            $table->decimal('interest_amount', 20, 2)->default(0.00); // Houses the 16% system markup value
            // -------------------------------
            
            $table->decimal('amount_paid', 20, 2)->default(0.00);
            $table->decimal('outstanding_debt', 20, 2)->nullable();
            
            // Schedule Metrics parameters
            $table->unsignedInteger('grace_period_days')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('payment_interval', ['weekly', 'monthly', 'quarterly', 'annually'])->default('monthly');
            
            $table->text('notes')->nullable();
            $table->json('payment_history')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};