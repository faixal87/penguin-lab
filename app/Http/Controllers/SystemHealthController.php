<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function __construct(private SystemHealthService $health)
    {
    }

    public function index(): View
    {
        return view('admin.health.index', [
            'snapshot' => $this->health->snapshot(),
        ]);
    }

    public function json(): JsonResponse
    {
        return response()->json($this->health->snapshot());
    }

    public function miniJson(): JsonResponse
    {
        return response()->json($this->health->mini());
    }
}
