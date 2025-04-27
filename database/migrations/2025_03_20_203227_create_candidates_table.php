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
            $table->string('firstname', 50);
            $table->string('surname', 50);
            $table->string('patronymic', 50);
            $table->string('location', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 50);
            $table->integer('age')->nullable();
            $table->string('quickInfo', 255)->nullable();
            $table->string('education', 100)->nullable();
            $table->string('link', 100)->nullable();
            $table->string('experience', 50)->nullable();
            $table->string('telegram', 50)->nullable();
            $table->string('skype', 50)->nullable();
            $table->string('imagePath', 50)->nullable();
            $table->boolean('isPng')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('resumePath', 50)->nullable();
            $table->string('resume', 50)->nullable();
            $table->string('coverPath', 50)->nullable();
            $table->foreignId('vacancy_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('stage_id')->constrained()->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('customers')->onDelete('cascade');
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
