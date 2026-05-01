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
        Schema::table('scenarios', function (Blueprint $table) {
            $table->unsignedTinyInteger('set_no')->nullable()->after('id');
            $table->string('question_type')->nullable()->after('set_no');

            $table->unique(['set_no', 'question_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropUnique(['set_no', 'question_type']);
            $table->dropColumn(['set_no', 'question_type']);
        });
    }
};
