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
            $table->foreignId('industry_id')->references('id')->on('industries');
            $table->foreignId('specializations_id')->references('id')->on('specializations');
            $table->foreignId('employment_id')->references('id')->on('employments');
            $table->foreignId('schedule_id')->references('id')->on('schedules');
            $table->foreignId('experience_id')->references('id')->on('experiences');
            $table->foreignId('education_id')->references('id')->on('education');
            $table->string('salary_from')->nullable();
            $table->string('salary_to')->nullable();
            $table->string('salary')->nullable();
            $table->foreignId('currency_id')->references('id')->on('currencies');
            $table->foreignId('place_id')->references('id')->on('places');
            $table->string('location')->nullable();
            $table->string('phrases')->nullable();
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
