<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_groups', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('email')->constrained('users')->nullOnDelete();
            $table->timestamp('setup_completed_at')->nullable()->after('created_by_user_id');
        });

        Schema::create('loan_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('full_name');
            $table->string('nin', 30);
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('sex', 10)->nullable();
            $table->boolean('is_group_leader')->default(false);
            $table->timestamps();

            $table->unique(['loan_group_id', 'nin']);
        });

        Schema::table('business_details', function (Blueprint $table) {
            $table->string('group_constitution')->nullable()->after('bank_statement');
            $table->string('group_muhtasari')->nullable()->after('group_constitution');
            $table->string('group_certificate')->nullable()->after('group_muhtasari');
        });
    }

    public function down(): void
    {
        Schema::table('business_details', function (Blueprint $table) {
            $table->dropColumn(['group_constitution', 'group_muhtasari', 'group_certificate']);
        });

        Schema::dropIfExists('loan_group_members');

        Schema::table('loan_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn('setup_completed_at');
        });
    }
};
