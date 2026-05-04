<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->string('difficulty')->nullable();
            $table->unsignedInteger('score')->default(10);
            $table->text('expected_answer');
            $table->text('hint_1')->nullable();
            $table->text('hint_2')->nullable();
            $table->text('explanation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('source_scenario_id')->nullable()->constrained('scenarios')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('scenarios')) {
            DB::table('scenarios')->orderBy('id')->get()->each(function ($scenario) {
                DB::table('question_bank')->insert([
                    'title' => $scenario->title,
                    'description' => $scenario->description,
                    'category' => $scenario->question_type ?? null,
                    'difficulty' => $scenario->difficulty,
                    'score' => $scenario->score,
                    'expected_answer' => $scenario->expected_command,
                    'hint_1' => $scenario->hint ?? null,
                    'hint_2' => $scenario->hint_2 ?? null,
                    'explanation' => null,
                    'is_active' => true,
                    'source_scenario_id' => $scenario->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};
