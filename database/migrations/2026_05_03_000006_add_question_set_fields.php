<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('question_set_id')->nullable()->after('terminal_enabled')->constrained('question_sets')->nullOnDelete();
        });

        Schema::table('student_answers', function (Blueprint $table) {
            $table->foreignId('question_set_id')->nullable()->after('scenario_id')->constrained('question_sets')->nullOnDelete();
            $table->foreignId('question_bank_id')->nullable()->after('question_set_id')->constrained('question_bank')->nullOnDelete();
            $table->foreignId('question_set_question_id')->nullable()->after('question_bank_id')->constrained('question_set_questions')->nullOnDelete();
            $table->unsignedTinyInteger('hint_level')->default(0)->after('hint_used');
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_set_question_id');
            $table->dropConstrainedForeignId('question_bank_id');
            $table->dropConstrainedForeignId('question_set_id');
            $table->dropColumn('hint_level');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_set_id');
        });
    }
};
