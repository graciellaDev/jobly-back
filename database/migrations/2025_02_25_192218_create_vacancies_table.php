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
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->unique()->nullable(false);
            $table->string('code')->unique()->nullable();
            $table->longText('description')->nullable(false);
            $table->string('specializations')->nullable();
            $table->string('employment')->nullable();
            $table->string('schedule')->nullable();
            $table->string('experience')->nullable();
            $table->string('education')->nullable();
            $table->string('salary_from')->nullable();
            $table->string('salary_to')->nullable();
            $table->string('salary')->nullable();
            $table->string('currency')->nullable();
            $table->string('place')->nullable();
            $table->string('location')->nullable();
            $table->text('phrases')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
