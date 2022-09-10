<?php

use App\Http\Controllers\SandboxController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/momo-collect', [SandboxController::class, 'collect']);
Route::post('/momo-deposit', [SandboxController::class, 'deposit']);
