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
        Schema::create('vacancy_platform', function (Blueprint $table) {
            $table->timestamps();
            $table->foreignId('vacancy_id')->constrained('vacancies')->onDelete('cascade');
            $table->string('platform')->nullable();
            $table->integer('platform_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancy_platform');
    }
};
