<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('vacancy_id')->nullable();
            $table->enum('type', ['system', 'note', 'call', 'task', 'email', 'chat', 'comment']);
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->string('author_name')->nullable();
            $table->string('channel')->nullable();
            $table->enum('direction', ['incoming', 'outgoing'])->nullable();

            $table->index(['candidate_id', 'vacancy_id', 'occurred_at'], 'events_candidate_vacancy_time');
            $table->index(['candidate_id', 'occurred_at'], 'events_candidate_time');

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->foreign('vacancy_id')->references('id')->on('vacancies')->nullOnDelete();
        });

        DB::statement('CREATE INDEX events_candidate_vacancy_time_desc ON events (candidate_id, vacancy_id, occurred_at DESC)');
        DB::statement('CREATE INDEX events_candidate_time_desc ON events (candidate_id, occurred_at DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
