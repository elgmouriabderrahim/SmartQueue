<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'app' => 'SmartQueue',
            'message' => 'Welcome to SmartQueue API.',
            'date' => now()->toDateString(),
        ]);
    }
}
