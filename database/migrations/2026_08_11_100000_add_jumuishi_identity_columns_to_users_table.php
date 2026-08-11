<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('global_user_id')->nullable()->unique()->after('id');
            $table->unsignedInteger('token_version')->default(0)->after('password');
            $table->string('jumuishi_sync_status', 32)->nullable()->after('token_version');
            $table->timestamp('jumuishi_synced_at')->nullable()->after('jumuishi_sync_status');
            $table->text('jumuishi_sync_error')->nullable()->after('jumuishi_synced_at');
        });

        Schema::create('jumuishi_processed_events', function (Blueprint $table) {
            $table->uuid('event_uuid')->primary();
            $table->string('event_type', 64);
            $table->foreignId('local_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumuishi_processed_events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'global_user_id',
                'token_version',
                'jumuishi_sync_status',
                'jumuishi_synced_at',
                'jumuishi_sync_error',
            ]);
        });
    }
};
