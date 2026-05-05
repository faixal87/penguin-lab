<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleModeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['admin', 'lecturer'])],
        ]);

        abort_unless($request->user()->canActAs($validated['mode']), 403);

        session(['active_role' => $validated['mode']]);

        return redirect()->route('dashboard')->with('status', 'Switched to ' . ucfirst($validated['mode']) . ' Mode.');
    }
}
