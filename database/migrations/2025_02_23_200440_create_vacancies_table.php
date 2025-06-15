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
            $table->string('name')->nullable(false);
            $table->string('code')->nullable();
            $table->longText('description')->nullable(false);
            $table->string('specializations')->nullable();
            $table->string('industry')->nullable();
            $table->string('employment')->nullable();
            $table->string('schedule')->nullable();
            $table->string('experience')->nullable();
            $table->string('education')->nullable();
            $table->integer('salary_from')->nullable();
            $table->integer('salary_to')->nullable();
            $table->string('salary_type')->nullable();
            $table->string('currency')->nullable();
            $table->foreignId('places')->nullable()->constrained()->onDelete('set null');
            $table->string('location')->nullable();
            $table->text('phrases')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('executor_name')->nullable();
            $table->string('executor_email')->nullable();
            $table->string('executor_phone')->nullable();
            $table->foreignId('executor_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('status', 50);
            $table->string('resource', 100)->nullable();
            $table->boolean('show_executor')->nullable();
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
