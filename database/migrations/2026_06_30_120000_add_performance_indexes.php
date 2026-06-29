<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draft_loans', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('officer_id');
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->index('loan_id');
        });

        Schema::table('applicant_loan_group', function (Blueprint $table) {
            $table->unique(['applicant_id', 'loan_group_id'], 'applicant_loan_group_unique');
        });
    }

    public function down(): void
    {
        Schema::table('draft_loans', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['officer_id']);
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->dropIndex(['loan_id']);
        });

        Schema::table('applicant_loan_group', function (Blueprint $table) {
            $table->dropUnique('applicant_loan_group_unique');
        });
    }
};
