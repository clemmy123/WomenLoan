<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->boolean('has_disability')->nullable()->after('loan_type');
            $table->boolean('is_widowed')->nullable()->after('has_disability');
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->string('application_letter')->nullable()->after('business_proposal_document');
            $table->string('bank_statement')->nullable()->after('application_letter');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['has_disability', 'is_widowed']);
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->dropColumn(['application_letter', 'bank_statement']);
        });
    }
};
