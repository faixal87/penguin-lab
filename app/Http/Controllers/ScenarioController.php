<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\StudentAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ScenarioController extends Controller
{
    public function index(): View
    {
        $this->ensureExamSetsExist();

        $examSet = $this->assignedExamSet();
        $scenarios = Scenario::where('set_no', $examSet)
            ->orderBy('id')
            ->get();
        $answersByScenario = StudentAnswer::where('user_id', request()->user()->id)
            ->where('exam_session_id', $this->examSessionId())
            ->whereIn('scenario_id', $scenarios->pluck('id'))
            ->get()
            ->keyBy('scenario_id');
        $totalAwarded = $answersByScenario->sum('score_awarded');
        $totalPossible = $scenarios->sum('score');

        return view('scenarios.index', compact(
            'scenarios',
            'examSet',
            'answersByScenario',
            'totalAwarded',
            'totalPossible'
        ));
    }

    public function show(Scenario $scenario): View
    {
        $this->ensureExamSetsExist();
        abort_unless($scenario->set_no === $this->assignedExamSet(), 404);

        $hintUsed = $this->hintUsed($scenario);

        return view('scenarios.show', compact('scenario', 'hintUsed'));
    }

    public function showHint(Scenario $scenario): RedirectResponse
    {
        abort_unless($scenario->set_no === $this->assignedExamSet(), 404);

        session([$this->hintSessionKey($scenario) => true]);

        return back()->withInput();
    }

    public function check(Request $request, Scenario $scenario): RedirectResponse
    {
        abort_unless($scenario->set_no === $this->assignedExamSet(), 404);

        $validated = $request->validate([
            'command' => ['required', 'string'],
        ]);

        $isCorrect = trim($validated['command']) === trim($scenario->expected_command);
        $hintUsed = $this->hintUsed($scenario);
        $awardedScore = 0;

        if ($isCorrect) {
            $awardedScore = $hintUsed ? $scenario->score * 0.5 : $scenario->score;
        }

        StudentAnswer::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'exam_session_id' => $this->examSessionId(),
                'scenario_id' => $scenario->id,
            ],
            [
                'set_no' => $this->assignedExamSet(),
                'answer' => $validated['command'],
                'is_correct' => $isCorrect,
                'score_awarded' => $awardedScore,
                'hint_used' => $hintUsed,
            ]
        );

        return back()
            ->withInput()
            ->with('answer_result', [
                'correct' => $isCorrect,
                'hint_used' => $hintUsed,
                'awarded_score' => $awardedScore,
            ]);
    }

    private function assignedExamSet(): int
    {
        if (! session()->has('exam_set')) {
            session(['exam_set' => random_int(1, 10)]);
        }

        return (int) session('exam_set');
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
