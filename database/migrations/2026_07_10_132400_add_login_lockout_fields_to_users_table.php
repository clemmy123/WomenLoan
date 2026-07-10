<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('is_active');
            $table->unsignedTinyInteger('login_lockout_rounds')->default(0)->after('failed_login_attempts');
            $table->timestamp('login_locked_until')->nullable()->after('login_lockout_rounds');
            $table->boolean('login_locked_permanently')->default(false)->after('login_locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'failed_login_attempts',
                'login_lockout_rounds',
                'login_locked_until',
                'login_locked_permanently',
            ]);
        });
    }
};
