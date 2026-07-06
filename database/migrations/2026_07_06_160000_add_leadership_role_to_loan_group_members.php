<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->string('leadership_role', 30)->nullable()->after('is_group_leader');
            $table->unique(['loan_group_id', 'leadership_role'], 'loan_group_members_group_leadership_unique');
        });
    }

    public function down(): void
    {
        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->dropUnique('loan_group_members_group_leadership_unique');
            $table->dropColumn('leadership_role');
        });
    }
};
