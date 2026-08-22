<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->string('status')->default('ACCEPTED')->after('remote_id');
            $table->unsignedBigInteger('initiated_by_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['status', 'initiated_by_id']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('username');
        });

        Schema::table('auth_sessions', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
