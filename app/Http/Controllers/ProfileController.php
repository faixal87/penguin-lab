<?php

namespace App\Http\Controllers;

use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private BadgeService $badgeService)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $totalScore = $user->studentAnswers()->sum('score_awarded');

        return view('profile', [
            'user' => $user,
            'totalScore' => $totalScore,
            'badge' => $this->badgeService->forScore($totalScore),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'phone_no' => ['nullable', 'string', 'max:50'],
            'program' => ['nullable', 'string', 'max:255'],
            'semester' => ['nullable', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'matric_no' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'registration_no' => ['nullable', 'string', 'max:255'],
            'staff_no' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $validated['profile_photo'] = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $user->update($validated);

        return back()->with('status', 'Profile updated successfully.');
    }
}
