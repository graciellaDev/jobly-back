<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->unique();
            $table->string('provider');
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('author_name');
            $table->string('company_name')->nullable();
            $table->text('content');
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'read']);
            $table->string('external_message_id')->nullable();
            $table->string('external_chat_id')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_message_id'], 'chats_provider_external_id');
            $table->index(['provider', 'external_chat_id'], 'chats_provider_chat_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events_chats');
    }
};
