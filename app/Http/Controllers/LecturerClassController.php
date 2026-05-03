<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LecturerClassController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('admin.classes.index', [
            'classes' => SchoolClass::with('lecturer', 'students')->latest('created_at')->get(),
            'lecturers' => User::where('role', 'lecturer')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:50'],
        ]);

        $lecturerId = $request->user()->isAdmin()
            ? $request->validate(['lecturer_id' => ['required', Rule::exists('users', 'id')->where('role', 'lecturer')]])['lecturer_id']
            : $request->user()->id;

        SchoolClass::create($validated + ['lecturer_id' => $lecturerId]);

        return back()->with('status', 'Class created successfully.');
    }

    public function edit(Request $request, SchoolClass $schoolClass): View
    {
        $this->authorizeClassManagement($request, $schoolClass);

        return view('lecturer.class-edit', [
            'class' => $schoolClass,
            'lecturers' => User::where('role', 'lecturer')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassManagement($request, $schoolClass);

        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:50'],
            'lecturer_id' => [
                $request->user()->isAdmin() ? 'required' : 'nullable',
                Rule::exists('users', 'id')->where('role', 'lecturer'),
            ],
        ]);

        if (! $request->user()->isAdmin()) {
            unset($validated['lecturer_id']);
        }

        $schoolClass->update($validated);

        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.classes.index')->with('status', 'Class updated successfully.');
        }

        return redirect()->route('lecturer.classes.show', $schoolClass)->with('status', 'Class updated successfully.');
    }

    public function destroy(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassManagement($request, $schoolClass);

        $schoolClass->delete();

        return redirect()
            ->route($request->user()->isAdmin() ? 'admin.classes.index' : 'dashboard')
            ->with('status', 'Class deleted successfully.');
    }

    public function show(Request $request, SchoolClass $schoolClass): View
    {
        $this->authorizeClassManagement($request, $schoolClass);

        return view('lecturer.class-show', [
            'class' => $schoolClass->load('students'),
        ]);
    }

    public function uploadStudents(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->authorizeClassManagement($request, $schoolClass);

        $request->validate([
            'students_csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $handle = fopen($request->file('students_csv')->getRealPath(), 'r');
        $headers = array_map(fn ($header) => trim((string) $header), fgetcsv($handle) ?: []);
        $requiredHeaders = ['name', 'matric_no', 'email', 'password'];
        $total = 0;
        $success = 0;
        $skipped = 0;
        $errors = [];
        $seenEmails = [];
        $seenMatricNos = [];
        $lineNumber = 1;

        if ($headers !== $requiredHeaders) {
            fclose($handle);

            return back()->with('import_summary', [
                'total' => 0,
                'success' => 0,
                'skipped' => 0,
                'errors' => [['row' => 1, 'reason' => 'CSV header must be: name,matric_no,email,password']],
            ]);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $rowNumber = $lineNumber;
            $row = array_map(fn ($value) => trim((string) $value), $row);

            if (count(array_filter($row, fn ($value) => $value !== '')) === 0) {
                continue;
            }

            $total++;

            if (count($row) !== count($headers)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Column count mismatch'];
                continue;
            }

            $data = array_combine($headers, $row);

            $validator = Validator::make($data ?: [], [
                'name' => ['required', 'string', 'max:255'],
                'matric_no' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => $validator->errors()->first()];
                continue;
            }

            $email = strtolower(trim($data['email']));
            $matricNo = trim($data['matric_no']);

            if (in_array($email, $seenEmails, true) || in_array($matricNo, $seenMatricNos, true)) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Duplicate email or matric_no inside CSV file.'];
                continue;
            }

            $seenEmails[] = $email;
            $seenMatricNos[] = $matricNo;

            $emailUser = User::where('email', $data['email'])->first();
            $matricUser = User::where('matric_no', $data['matric_no'])->first();

            if ($emailUser && $matricUser && $emailUser->id !== $matricUser->id) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Email and matric_no belong to different users.'];
                continue;
            }

            if ($emailUser && $emailUser->matric_no && $emailUser->matric_no !== $data['matric_no']) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Email already exists with another matric_no.'];
                continue;
            }

            if ($matricUser && $matricUser->email !== $data['email']) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Matric_no already exists with another email.'];
                continue;
            }

            $student = $emailUser ?: $matricUser ?: User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'matric_no' => $data['matric_no'],
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'status' => 'approved',
            ]);

            if ($student->role !== 'student') {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Existing account is not a student.'];
                continue;
            }

            if (! $student->matric_no) {
                $student->update(['matric_no' => $data['matric_no']]);
            }

            if ($schoolClass->students()->where('users.id', $student->id)->exists()) {
                $skipped++;
                $errors[] = ['row' => $rowNumber, 'reason' => 'Student is already enrolled in this class.'];
                continue;
            }

            $schoolClass->students()->syncWithoutDetaching([$student->id]);
            $success++;
        }

        fclose($handle);

        return back()->with('import_summary', [
            'total' => $total,
            'success' => $success,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    private function authorizeClassManagement(Request $request, SchoolClass $schoolClass): void
    {
        abort_unless(
            $request->user()->isAdmin() || $schoolClass->lecturer_id === $request->user()->id,
            403
        );
    }
}
