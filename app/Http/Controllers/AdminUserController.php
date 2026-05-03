<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50], true) ? $perPage : 10;
        $search = trim((string) $request->query('search', ''));
        $searchLower = strtolower($search);

        $users = User::query()
            ->when($search !== '', function ($query) use ($searchLower) {
                $query->where(function ($inner) use ($searchLower) {
                    $inner->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(matric_no) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(registration_no) LIKE ?', ["%{$searchLower}%"]);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'perPage' => $perPage,
            'search' => $search,
        ]);
    }

    public function createLecturer(): View
    {
        return view('admin.users.create', ['role' => 'lecturer']);
    }

    public function createStudent(): View
    {
        return view('admin.users.create', ['role' => 'student']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request, null, true));

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('admin.users.index')->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->rules($request, $user, false));

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('status', 'User updated successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('status', 'Password reset successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $result = $this->deleteUserSafely($user, $request->user()->id);

        $redirect = redirect()->route('admin.users.index', $request->only(['search', 'per_page', 'page']));

        if (! $result['deleted']) {
            return $redirect->with('error', $result['reason']);
        }

        return $redirect->with('status', 'User deleted successfully.');
    }

    public function batchDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $deleted = 0;
        $skipped = [];
        $users = User::whereIn('id', $validated['user_ids'])->get();

        foreach ($users as $user) {
            $result = $this->deleteUserSafely($user, $request->user()->id);

            if ($result['deleted']) {
                $deleted++;
            } else {
                $skipped[] = [
                    'user' => $user->name . ' (' . $user->email . ')',
                    'reason' => $result['reason'],
                ];
            }
        }

        $skippedCount = count($skipped);
        $message = "Batch delete complete. Deleted: {$deleted}. Skipped: {$skippedCount}.";

        return redirect()
            ->route('admin.users.index', $request->only(['search', 'per_page', 'page']))
            ->with('status', $message)
            ->with('delete_summary', [
                'deleted' => $deleted,
                'skipped' => $skippedCount,
                'skipped_users' => $skipped,
            ]);
    }

    private function deleteUserSafely(User $user, int $currentAdminId): array
    {
        if ($user->id === $currentAdminId) {
            return [
                'deleted' => false,
                'reason' => 'You cannot delete your own admin account while logged in.',
            ];
        }

        if ($user->isLecturer() && $user->teachingClasses()->exists()) {
            return [
                'deleted' => false,
                'reason' => 'Lecturer owns classes. Reassign or delete those classes first.',
            ];
        }

        DB::transaction(function () use ($user) {
            $this->deleteRowsByColumn('class_student', 'student_id', $user->id);
            $this->deleteRowsByColumn('student_answers', 'user_id', $user->id);
            $this->deleteRowsByColumn('login_logs', 'user_id', $user->id);
            $this->deleteRowsByColumn('feedback_answers', 'user_id', $user->id);

            $user->delete();
        });

        return [
            'deleted' => true,
            'reason' => null,
        ];
    }

    private function deleteRowsByColumn(string $table, string $column, int $userId): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->where($column, $userId)->delete();
        }
    }

    private function rules(Request $request, ?User $user, bool $creating): array
    {
        $userId = $user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'lecturer', 'student'])],
            'status' => ['required', 'string', 'max:50'],
            'matric_no' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'registration_no' => ['nullable', 'string', 'max:255'],
            'staff_no' => ['nullable', 'string', 'max:255'],
            'phone_no' => ['nullable', 'string', 'max:50'],
            'program' => ['nullable', 'string', 'max:255'],
            'semester' => ['nullable', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'linux_username' => ['nullable', 'string', 'max:255'],
            'container_name' => ['nullable', 'string', 'max:255'],
            'terminal_enabled' => ['nullable', 'boolean'],
        ];
    }
}
