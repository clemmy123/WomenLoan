<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('preferred_loan_type', 20)->nullable()->after('marital_status');
            $table->boolean('has_disability')->nullable()->after('preferred_loan_type');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['preferred_loan_type', 'has_disability']);
        });
    }
};
