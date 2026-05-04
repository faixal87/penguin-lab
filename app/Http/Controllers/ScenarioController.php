<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\StudentAnswer;
use App\Models\QuestionSet;
use App\Models\QuestionSetQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function chooseSet(Request $request): View|RedirectResponse
    {
        $sets = $this->availableQuestionSets($request);

        if ($sets->isEmpty()) {
            return redirect()->route('scenarios');
        }

        if ($sets->count() === 1) {
            session(['selected_question_set_id' => $sets->first()->id]);

            return redirect()->route('scenarios');
        }

        return view('scenarios.choose', compact('sets'));
    }

    public function selectSet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question_set_id' => ['required', 'integer', 'exists:question_sets,id'],
        ]);

        $sets = $this->availableQuestionSets($request);
        abort_unless($sets->contains('id', (int) $validated['question_set_id']), 403);

        session(['selected_question_set_id' => (int) $validated['question_set_id']]);

        return redirect()->route('scenarios');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $availableSets = $this->availableQuestionSets($request);

        if ($availableSets->count() > 1 && ! $this->selectedQuestionSet($request, $availableSets)) {
            return redirect()->route('scenarios.choose');
        }

        if ($assignedSet = $this->selectedQuestionSet($request, $availableSets)) {
            $setQuestions = $assignedSet->setQuestions()->with('question')->get();
            $answersByScenario = StudentAnswer::where('user_id', $request->user()->id)
                ->where('exam_session_id', $this->examSessionId())
                ->whereIn('question_set_question_id', $setQuestions->pluck('id'))
                ->get()
                ->keyBy('question_set_question_id');
            $totalAwarded = $answersByScenario->sum('score_awarded');
            $totalPossible = $setQuestions->sum(fn (QuestionSetQuestion $item) => $item->effectiveMark());
            $examSet = $assignedSet->name;
            $usingQuestionSet = true;

            return view('scenarios.index', compact('setQuestions', 'examSet', 'answersByScenario', 'totalAwarded', 'totalPossible', 'usingQuestionSet', 'availableSets'));
        }

        $usingQuestionSet = true;
        $noAssignedSet = true;
        $examSet = null;
        $setQuestions = collect();
        $answersByScenario = collect();
        $totalAwarded = 0;
        $totalPossible = 0;

        return view('scenarios.index', compact(
            'setQuestions',
            'examSet',
            'answersByScenario',
            'totalAwarded',
            'totalPossible',
            'usingQuestionSet',
            'noAssignedSet',
            'availableSets'
        ));
    }

    public function show(Scenario $scenario): View
    {
        abort(404);
    }

    public function showHint(Scenario $scenario): RedirectResponse
    {
        abort(404);
    }

    public function check(Request $request, Scenario $scenario): RedirectResponse
    {
        abort(404);
    }

    public function showSetQuestion(QuestionSetQuestion $setQuestion): View
    {
        abort_unless($this->canAccessSetQuestion($setQuestion), 404);

        $question = $setQuestion->load('question', 'questionSet')->question;
        $scenario = (object) [
            'id' => $setQuestion->id,
            'title' => $question->title,
            'description' => $question->description,
            'difficulty' => $question->difficulty,
            'score' => $setQuestion->effectiveMark(),
            'hint' => $question->hint_1,
            'explanation' => $question->explanation,
        ];
        $hintUsed = $this->setQuestionHintUsed($setQuestion);
        $usingQuestionSet = true;

        return view('scenarios.show', compact('scenario', 'hintUsed', 'setQuestion', 'usingQuestionSet'));
    }

    public function showSetQuestionHint(QuestionSetQuestion $setQuestion, int $level): RedirectResponse
    {
        abort_unless($this->canAccessSetQuestion($setQuestion), 404);
        abort_unless($level === 1, 404);

        session([$this->setQuestionHintSessionKey($setQuestion) => true]);

        return back()->withInput();
    }

    public function checkSetQuestion(Request $request, QuestionSetQuestion $setQuestion): RedirectResponse
    {
        abort_unless($this->canAccessSetQuestion($setQuestion), 404);

        $validated = $request->validate(['command' => ['required', 'string']]);
        $setQuestion->load('question', 'questionSet');
        $isCorrect = trim($validated['command']) === trim($setQuestion->question->expected_answer);
        $hintUsed = $this->setQuestionHintUsed($setQuestion);
        $mark = $setQuestion->effectiveMark();
        $awardedScore = 0;

        if ($isCorrect) {
            $awardedScore = $hintUsed ? $mark * 0.5 : $mark;
        }

        StudentAnswer::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'exam_session_id' => $this->examSessionId(),
                'question_set_question_id' => $setQuestion->id,
            ],
            [
                'set_no' => 0,
                'scenario_id' => $setQuestion->question->source_scenario_id ?: $this->legacyScenarioForQuestion($setQuestion),
                'question_set_id' => $setQuestion->question_set_id,
                'question_bank_id' => $setQuestion->question_bank_id,
                'answer' => $validated['command'],
                'is_correct' => $isCorrect,
                'score_awarded' => $awardedScore,
                'hint_used' => $hintUsed,
                'hint_level' => $hintUsed ? 1 : 0,
            ]
        );

        return back()->withInput()->with('answer_result', [
            'correct' => $isCorrect,
            'hint_used' => $hintUsed,
            'awarded_score' => $awardedScore,
        ]);
    }

    private function assignedQuestionSet(): ?QuestionSet
    {
        return $this->selectedQuestionSet(request(), $this->availableQuestionSets(request()));
    }

    private function availableQuestionSets(Request $request)
    {
        $user = $request->user();
        $studentSets = $user->assignedQuestionSets()
            ->where('is_active', true)
            ->with('setQuestions.question')
            ->get()
            ->filter(fn (QuestionSet $set) => $set->isAssignable())
            ->values();

        if ($studentSets->isNotEmpty()) {
            return $studentSets;
        }

        return QuestionSet::query()
            ->where('is_active', true)
            ->whereHas('classes', fn ($query) => $query->whereIn('classes.id', $user->enrolledClasses()->pluck('classes.id')))
            ->with('setQuestions.question')
            ->get()
            ->filter(fn (QuestionSet $set) => $set->isAssignable())
            ->values();
    }

    private function selectedQuestionSet(Request $request, $availableSets): ?QuestionSet
    {
        if ($availableSets->isEmpty()) {
            session()->forget('selected_question_set_id');
            return null;
        }

        if ($availableSets->count() === 1) {
            session(['selected_question_set_id' => $availableSets->first()->id]);
            return $availableSets->first();
        }

        $selectedId = (int) session('selected_question_set_id', 0);
        $selected = $availableSets->firstWhere('id', $selectedId);

        if (! $selected) {
            session()->forget('selected_question_set_id');
        }

        return $selected;
    }

    private function canAccessSetQuestion(QuestionSetQuestion $setQuestion): bool
    {
        $set = $this->assignedQuestionSet();

        return $set && $setQuestion->question_set_id === $set->id;
    }

    private function setQuestionHintUsed(QuestionSetQuestion $setQuestion): bool
    {
        return (bool) session($this->setQuestionHintSessionKey($setQuestion), false);
    }

    private function setQuestionHintSessionKey(QuestionSetQuestion $setQuestion): string
    {
        return "set_question_hints.{$setQuestion->id}";
    }

    private function legacyScenarioForQuestion(QuestionSetQuestion $setQuestion): int
    {
        $question = $setQuestion->question;

        $scenario = Scenario::firstOrCreate(
            [
                'set_no' => 1,
                'question_type' => 'question_bank_' . $question->id,
            ],
            [
                'title' => $question->title,
                'description' => $question->description,
                'expected_command' => $question->expected_answer,
                'hint' => $question->hint_1,
                'hint_2' => $question->hint_2,
                'difficulty' => $question->difficulty,
                'score' => $question->score,
            ]
        );

        $question->update(['source_scenario_id' => $scenario->id]);

        return $scenario->id;
    }

    private function examSessionId(): string
    {
        if (! session()->has('exam_session_id')) {
            session(['exam_session_id' => (string) Str::uuid()]);
        }

        return (string) session('exam_session_id');
    }

    private function ensureExamSetsExist(): void
    {
        foreach (range(1, 10) as $setNo) {
            foreach ($this->questionsForSet($setNo) as $question) {
                Scenario::firstOrCreate(
                    [
                        'set_no' => $setNo,
                        'question_type' => $question['question_type'],
                    ],
                    [
                        'title' => $question['title'],
                        'description' => $question['description'],
                        'expected_command' => $question['expected_command'],
                        'hint' => $question['hint'],
                        'difficulty' => $question['difficulty'],
                        'score' => $question['score'],
                    ]
                )->update([
                    'hint' => $question['hint'],
                ]);
            }
        }
    }

    private function hintUsed(Scenario $scenario): bool
    {
        return (bool) session($this->hintSessionKey($scenario), false);
    }

    private function hintSessionKey(Scenario $scenario): string
    {
        return "scenario_hints.{$scenario->id}";
    }

    private function questionsForSet(int $setNo): array
    {
        $folder = "project_set_{$setNo}";
        $file = "report_{$setNo}.txt";
        $copy = "report_{$setNo}_copy.txt";
        $moved = "final_report_{$setNo}.txt";
        $user = "student{$setNo}";
        $permissions = [700, 755, 644, 600, 664, 775, 640, 744, 711, 750][$setNo - 1];
        $keyword = ['error', 'warning', 'failed', 'success', 'timeout', 'denied', 'active', 'pending', 'shellfix', 'network'][$setNo - 1];
        $interface = ['eth0', 'ens33', 'wlan0', 'enp0s3', 'eth1', 'ens160', 'wlp2s0', 'eno1', 'enp3s0', 'docker0'][$setNo - 1];

        return [
            [
                'question_type' => 'mkdir',
                'title' => 'Create a directory',
                'description' => "Create a new folder named {$folder}.",
                'expected_command' => "mkdir {$folder}",
                'hint' => 'Use mkdir followed by the folder name.',
                'difficulty' => 'Easy',
                'score' => 10,
            ],
            [
                'question_type' => 'touch',
                'title' => 'Create a file',
                'description' => "Create an empty file named {$file}.",
                'expected_command' => "touch {$file}",
                'hint' => 'Use touch followed by the file name.',
                'difficulty' => 'Easy',
                'score' => 10,
            ],
            [
                'question_type' => 'cp',
                'title' => 'Copy a file',
                'description' => "Copy {$file} to {$copy}.",
                'expected_command' => "cp {$file} {$copy}",
                'hint' => 'Use cp followed by the source file and destination file.',
                'difficulty' => 'Easy',
                'score' => 10,
            ],
            [
                'question_type' => 'mv',
                'title' => 'Move or rename a file',
                'description' => "Rename {$copy} to {$moved}.",
                'expected_command' => "mv {$copy} {$moved}",
                'hint' => 'Use mv followed by the current name and new name.',
                'difficulty' => 'Easy',
                'score' => 10,
            ],
            [
                'question_type' => 'chmod',
                'title' => 'Change file permissions',
                'description' => "Set permission {$permissions} on {$moved}.",
                'expected_command' => "chmod {$permissions} {$moved}",
                'hint' => 'Use chmod followed by the permission number and file name.',
                'difficulty' => 'Medium',
                'score' => 15,
            ],
            [
                'question_type' => 'chown',
                'title' => 'Change file owner',
                'description' => "Change owner of {$moved} to {$user}.",
                'expected_command' => "chown {$user} {$moved}",
                'hint' => 'Use chown followed by the username and file name.',
                'difficulty' => 'Medium',
                'score' => 15,
            ],
            [
                'question_type' => 'id',
                'title' => 'Check user identity',
                'description' => "Show identity information for user {$user}.",
                'expected_command' => "id {$user}",
                'hint' => 'Use id followed by the username.',
                'difficulty' => 'Easy',
                'score' => 10,
            ],
            [
                'question_type' => 'grep',
                'title' => 'Search inside a file',
                'description' => "Search for keyword {$keyword} inside {$file}.",
                'expected_command' => "grep {$keyword} {$file}",
                'hint' => 'Use grep followed by the keyword and file name.',
                'difficulty' => 'Medium',
                'score' => 15,
            ],
            [
                'question_type' => 'find',
                'title' => 'Find a file',
                'description' => "Find {$file} inside the {$folder} folder.",
                'expected_command' => "find {$folder} -name {$file}",
                'hint' => 'Use find with the folder first, then -name and the file name.',
                'difficulty' => 'Medium',
                'score' => 15,
            ],
            [
                'question_type' => 'ip address check',
                'title' => 'Check IP address',
                'description' => "Show IP address information for network interface {$interface}.",
                'expected_command' => "ip address show {$interface}",
                'hint' => 'Use ip address show followed by the network interface name.',
                'difficulty' => 'Medium',
                'score' => 15,
            ],
        ];
    }
}
