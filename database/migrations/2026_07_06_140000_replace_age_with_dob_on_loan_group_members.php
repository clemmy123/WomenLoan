<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('nin');
        });

        foreach (DB::table('loan_group_members')->whereNotNull('age')->get() as $member) {
            DB::table('loan_group_members')
                ->where('id', $member->id)
                ->update([
                    'dob' => now()->subYears((int) $member->age)->startOfYear()->toDateString(),
                ]);
        }

        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->dropColumn('age');
        });
    }

    public function down(): void
    {
        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after('nin');
        });

        foreach (DB::table('loan_group_members')->whereNotNull('dob')->get() as $member) {
            $age = \Illuminate\Support\Carbon::parse($member->dob)->age;

            DB::table('loan_group_members')
                ->where('id', $member->id)
                ->update(['age' => min(120, max(0, $age))]);
        }

        Schema::table('loan_group_members', function (Blueprint $table) {
            $table->dropColumn('dob');
        });
    }
};
