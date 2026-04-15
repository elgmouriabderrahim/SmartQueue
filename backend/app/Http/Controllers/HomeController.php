<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success([
            'app' => 'SmartQueue',
            'date' => now()->toDateString(),
        ], 'Welcome to SmartQueue API.');
    }
}
