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
        Schema::create('candidate_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->string('value');
            $table->unsignedBigInteger('custom_field_id');
            $table->unsignedBigInteger('candidate_id');

            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_custom_field_values');
    }
};
