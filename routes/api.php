<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpenAIController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/google/login', [AuthController::class, 'googleLogin']);


Route::get('/session/en', [OpenAIController::class, 'createEphemeralTokenEN']);
Route::get('/session/me', [OpenAIController::class, 'createEphemeralTokenME']);
