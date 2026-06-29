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
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // The officer operating this level
            
            // Explicit tracking matching your business workflow (Steps 1 to 8)
            $table->unsignedTinyInteger('step_number'); // 1, 2, 3, 4, 5, 6, 7, 8
            $table->string('action_taken'); // 'attached_minute', 'proposed_amount', 'accepted', 'forwarded', etc.
            
            // Financial capturing column matching your business calculations
            $table->decimal('proposed_amount', 20, 2)->default(0.00);
            
            // Explicit Document Attachments tracking column
            // Serves for both ward_minute_attachment (Step 1) and verifier_1_attachment (Step 4)
            $table->string('attachment_path')->nullable(); 
            
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_levels');
    }
};