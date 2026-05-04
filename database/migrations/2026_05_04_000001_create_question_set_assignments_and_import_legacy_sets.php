<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_question_set', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('question_set_id')->constrained('question_sets')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_id', 'question_set_id']);
        });

        Schema::create('question_set_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_set_id')->constrained('question_sets')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'question_set_id']);
        });

        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'question_set_id')) {
            DB::table('classes')
                ->whereNotNull('question_set_id')
                ->orderBy('id')
                ->get(['id', 'question_set_id'])
                ->each(function ($class) {
                    DB::table('class_question_set')->updateOrInsert(
                        [
                            'class_id' => $class->id,
                            'question_set_id' => $class->question_set_id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }

        if (! Schema::hasTable('scenarios') || ! Schema::hasTable('question_bank') || ! Schema::hasTable('question_sets')) {
            return;
        }

        foreach (range(1, 10) as $setNo) {
            $scenarios = DB::table('scenarios')
                ->where('set_no', $setNo)
                ->orderBy('id')
                ->get();

            if ($scenarios->isEmpty()) {
                continue;
            }

            $setId = DB::table('question_sets')
                ->where('name', "Prebuilt Set {$setNo}")
                ->value('id');

            if (! $setId) {
                $setId = DB::table('question_sets')->insertGetId([
                    'created_by' => null,
                    'visibility' => 'shared',
                    'name' => "Prebuilt Set {$setNo}",
                    'description' => "Imported legacy ShellFix prebuilt exam set {$setNo}.",
                    'total_marks' => 100,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('question_sets')->where('id', $setId)->update([
                    'visibility' => 'shared',
                    'total_marks' => 100,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            }

            foreach ($scenarios->values() as $index => $scenario) {
                $questionId = DB::table('question_bank')
                    ->where('source_scenario_id', $scenario->id)
                    ->value('id');

                if (! $questionId) {
                    $questionId = DB::table('question_bank')->insertGetId([
                        'created_by' => null,
                        'visibility' => 'shared',
                        'title' => $scenario->title,
                        'description' => $scenario->description,
                        'category' => $scenario->question_type ?? null,
                        'difficulty' => $scenario->difficulty,
                        'score' => 10,
                        'expected_answer' => $scenario->expected_command,
                        'hint_1' => $scenario->hint ?? null,
                        'hint_2' => null,
                        'explanation' => null,
                        'is_active' => true,
                        'source_scenario_id' => $scenario->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('question_set_questions')->updateOrInsert(
                    [
                        'question_set_id' => $setId,
                        'question_bank_id' => $questionId,
                    ],
                    [
                        'mark' => 10,
                        'sort_order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_set_user');
        Schema::dropIfExists('class_question_set');
    }
};
