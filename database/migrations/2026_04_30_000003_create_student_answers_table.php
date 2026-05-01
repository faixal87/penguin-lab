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
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->string('exam_session_id');
            $table->unsignedTinyInteger('set_no');
            $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
            $table->text('answer');
            $table->boolean('is_correct')->default(false);
            $table->decimal('score_awarded', 8, 2)->default(0);
            $table->boolean('hint_used')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['exam_session_id', 'scenario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
