<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('check_number', 50)->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('check_number');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
        });

        $users = DB::table('users')->select('id', 'name')->get();

        foreach ($users as $user) {
            $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
            $parts = array_values(array_filter($parts));

            $first = $parts[0] ?? null;
            $last = count($parts) > 1 ? $parts[array_key_last($parts)] : null;
            $middle = count($parts) > 2
                ? implode(' ', array_slice($parts, 1, -1))
                : null;

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['check_number', 'first_name', 'middle_name', 'last_name']);
        });
    }
};
