<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        ];
    }
}
