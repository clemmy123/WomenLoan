<?php

use App\Models\Concerns\HasDisplayName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurantors', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('applicant_id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
        });

        foreach (DB::table('gurantors')->whereNotNull('name')->get() as $guarantor) {
            $parts = HasDisplayName::splitFullName((string) $guarantor->name);

            DB::table('gurantors')
                ->where('id', $guarantor->id)
                ->update([
                    'first_name' => $parts['first_name'] ?: null,
                    'middle_name' => $parts['middle_name'],
                    'last_name' => $parts['last_name'] ?: null,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('gurantors', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name']);
        });
    }
};
