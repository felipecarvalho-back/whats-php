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
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'idx_messages_conv_created');
            $table->index(['conversation_id', 'status'], 'idx_messages_conv_status');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['status', 'last_message_at'], 'idx_conversations_status_last_msg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conversations_status_last_msg');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conv_status');
            $table->dropIndex('idx_messages_conv_created');
        });
    }
};
