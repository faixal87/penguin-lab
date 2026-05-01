<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->string('category');
            $table->boolean('is_active')->default(true);
        });

        DB::table('feedback_questions')->insert([
            ['question_text' => 'The ShellFix interface is easy to navigate.', 'category' => 'usability', 'is_active' => true],
            ['question_text' => 'The instructions on each page are clear.', 'category' => 'usability', 'is_active' => true],
            ['question_text' => 'I can easily find the scenarios and results pages.', 'category' => 'usability', 'is_active' => true],
            ['question_text' => 'The feedback and score displays are easy to understand.', 'category' => 'usability', 'is_active' => true],
            ['question_text' => 'ShellFix helps me understand Linux commands better.', 'category' => 'learning effectiveness', 'is_active' => true],
            ['question_text' => 'The scenarios improve my confidence using command-line tools.', 'category' => 'learning effectiveness', 'is_active' => true],
            ['question_text' => 'The hints help me learn without giving away too much.', 'category' => 'learning effectiveness', 'is_active' => true],
            ['question_text' => 'The randomized question sets support fair practice and assessment.', 'category' => 'learning effectiveness', 'is_active' => true],
            ['question_text' => 'I feel motivated to complete all ShellFix scenarios.', 'category' => 'engagement', 'is_active' => true],
            ['question_text' => 'The badge system encourages me to improve my score.', 'category' => 'engagement', 'is_active' => true],
            ['question_text' => 'The class ranking makes the activity more engaging.', 'category' => 'engagement', 'is_active' => true],
            ['question_text' => 'The scenario tasks are interesting enough to keep me focused.', 'category' => 'engagement', 'is_active' => true],
            ['question_text' => 'The answer checking system gives useful immediate feedback.', 'category' => 'assessment', 'is_active' => true],
            ['question_text' => 'The scoring system is fair and easy to understand.', 'category' => 'assessment', 'is_active' => true],
            ['question_text' => 'The hint penalty is reasonable for assessment purposes.', 'category' => 'assessment', 'is_active' => true],
            ['question_text' => 'The result page clearly shows my performance.', 'category' => 'assessment', 'is_active' => true],
            ['question_text' => 'The scenario difficulty levels feel appropriate.', 'category' => 'learning effectiveness', 'is_active' => true],
            ['question_text' => 'The system helps me identify which commands I need to review.', 'category' => 'learning effectiveness', 'is_active' => true],
            ['question_text' => 'The overall ShellFix experience is enjoyable.', 'category' => 'engagement', 'is_active' => true],
            ['question_text' => 'I would recommend ShellFix for learning Linux basics.', 'category' => 'usability', 'is_active' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_questions');
    }
};
