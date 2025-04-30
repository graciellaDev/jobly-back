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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('position', 50);
            $table->string('division', 50)->nullable();
            $table->integer('count')->nullable();
            $table->integer('salaryFrom')->nullable();
            $table->integer('salaryTo')->nullable();
            $table->string('currency', 50)->nullable();
            $table->string('require', 200)->nullable();
            $table->string('duty', 200)->nullable();
            $table->string('reason', 50)->nullable();
            $table->date('dateStart')->nullable();
            $table->date('dateWork')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('vacancy_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('status_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('executor_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('city')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
