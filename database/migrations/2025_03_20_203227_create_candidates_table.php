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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 100);
            $table->string('job', 255);
            $table->string('location', 100);
            $table->string('phone', 50);
            $table->string('email', 50);
            $table->string('description', 255);
            $table->string('education', 100);
            $table->string('link', 100)->nullable();
            $table->string('vacancy', 100);
            $table->string('experience', 50);
            $table->string('telegram', 50)->nullable();
            $table->string('skype', 50)->nullable();
            $table->string('imagePath', 50)->nullable();
            $table->string('resumePath', 50)->nullable();
            $table->string('coverPath', 50)->nullable();
            $table->foreignId('customer_id')->references('id')->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
