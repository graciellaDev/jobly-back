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
        Schema::create('candidate_platform', function (Blueprint $table) {
            $table->timestamps();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->string('platform')->nullable();
            $table->integer('platform_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_platform');
    }
};
