<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->string('marital_status', 20)->nullable()->after('sex');
        });

        Schema::table('gurantors', function (Blueprint $table) {
            $table->string('guarantor_letter')->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('gurantors', function (Blueprint $table) {
            $table->dropColumn('guarantor_letter');
        });

        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->dropColumn('marital_status');
        });
    }
};
