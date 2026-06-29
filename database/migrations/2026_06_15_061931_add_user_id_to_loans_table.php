<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to update the loans table.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Adding user_id as a nullable column to avoid database errors
            // Positioned after the 'id' column for better table structure
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations by dropping the user_id column.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};