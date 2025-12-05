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
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('coverPath');
            $table->string('messengerMax', 50)->nullable()->after('telegram');
            $table->boolean('isReserve')->default(false)->nullable()->after('source');
            $table->string('patronymic', 50)->nullable()->change();
            $table->string('surname', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->dropColumn('messengerMax');
            $table->dropColumn('isReserve');
            $table->string('patronymic', 50)->nullable(false)->change();
            $table->string('surname', 50)->nullable(false)->change();
        });
    }
};
