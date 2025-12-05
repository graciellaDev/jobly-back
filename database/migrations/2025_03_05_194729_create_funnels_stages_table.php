<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('funnel_stage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('funnel_id');
            $table->unsignedBigInteger('stage_id');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');

            $table->foreign('funnel_id')->references('id')->on('funnels')->onDelete('cascade');
            $table->foreign('stage_id')->references('id')->on('stages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_funnel_stage', function (Blueprint $table) {
            $table->dropForeign(['funnel_stage_id']);
        });
        Schema::dropIfExists('funnel_stage');
    }
};
