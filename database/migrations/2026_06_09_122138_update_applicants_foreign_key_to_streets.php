<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Ondoa uhusiano wa zamani wa locations
            $table->dropForeign(['location_id']);
            
            // Unganisha na meza sahihi ya 'streets'
            $table->foreign('location_id')
                  ->references('id')
                  ->on('streets')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            
            // Rudisha uhusiano wa zamani kama utafanya rollback
            $table->foreign('location_id')
                  ->references('id')
                  ->on('locations')
                  ->onDelete('set null');
        });
    }
};