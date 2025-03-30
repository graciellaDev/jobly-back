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
        Schema::create('condition_vacancy', function (Blueprint $table) {
//            $table->unsignedBigInteger('vacancy_id');
//            $table->unsignedBigInteger('condition_id');
//            $table->timestamps();

//            $table->foreignId('condition_id')->references('id')->on('conditions')->onDelete('cascade');
//            $table->foreignId('vacancy_id')->references('id')->on('vacancies')->onDelete('cascade');

            $table->id();
            $table->foreignId('condition_id')->constrained()->onDelete('cascade');
            $table->foreignId('vacancy_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_vacancy');
    }
};
