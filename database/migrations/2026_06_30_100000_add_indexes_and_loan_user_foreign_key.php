<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
            $table->index('current_step');
            $table->index(['status', 'current_step']);
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('approval_levels', function (Blueprint $table) {
            $table->index(['loan_id', 'step_number']);
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->index('ward_id');
            $table->index('council_id');
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['current_step']);
            $table->dropIndex(['status', 'current_step']);
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('approval_levels', function (Blueprint $table) {
            $table->dropIndex(['loan_id', 'step_number']);
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->dropIndex(['ward_id']);
            $table->dropIndex(['council_id']);
            $table->dropIndex(['region_id']);
        });
    }
};
