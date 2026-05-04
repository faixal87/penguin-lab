<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('total_marks')->default(100);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('question_set_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();
            $table->unsignedInteger('mark')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_set_questions');
        Schema::dropIfExists('question_sets');
    }
};
