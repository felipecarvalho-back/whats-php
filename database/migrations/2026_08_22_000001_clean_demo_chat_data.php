<?php

use App\Models\AuthSession;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Message::query()->truncate();
        Conversation::query()->truncate();
        Contact::query()->truncate();
        AuthSession::query()->truncate();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
