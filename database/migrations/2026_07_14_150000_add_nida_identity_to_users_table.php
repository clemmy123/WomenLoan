<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nin', 20)->nullable()->unique()->after('phone');
            $table->date('dob')->nullable()->after('nin');
            $table->string('sex', 20)->nullable()->after('dob');
            $table->string('nationality')->nullable()->after('sex');
            $table->string('nida_photo_path')->nullable()->after('nationality');
            $table->timestamp('nida_verified_at')->nullable()->after('nida_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nin',
                'dob',
                'sex',
                'nationality',
                'nida_photo_path',
                'nida_verified_at',
            ]);
        });
    }
};
