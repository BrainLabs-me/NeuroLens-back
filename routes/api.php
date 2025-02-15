<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\EegReadingController;
use App\Http\Controllers\EegStatsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/google/login', [AuthController::class, 'googleLogin']);


Route::get('/session/en', [OpenAIController::class, 'createEphemeralTokenEN']);
Route::get('/session/me', [OpenAIController::class, 'createEphemeralTokenME']);


Route::post('/send-message', [OpenAIController::class, 'sendMessage'])->middleware('auth:sanctum');
Route::post('/start-chat', [OpenAIController::class, 'createThread'])->middleware('auth:sanctum');
Route::post('/audio', [OpenAIController::class, 'streamAudio']);


Route::post('/send-message/guest', [OpenAIController::class, 'sendMessageGuest']);
Route::get('/start-chat/guest', [OpenAIController::class, 'createThreadGuest']);
Route::post('/audio', [OpenAIController::class, 'streamAudio']);

Route::post('forgot-password/send-otp', [AuthController::class, 'sendOtp']);
Route::post('forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::resource('/messages', MessageController::class)->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function() {
    // Endpoint za agregaciju podataka po sekundi (npr. pokreće se periodično ili na zahtjev)
    Route::post('/eeg/aggregate-second-stats', [EegStatsController::class, 'aggregateSecondStats']);
    
    // Endpoint za dohvaćanje focus rate-a (zadnji sat i trenutni)
    Route::get('/eeg/focus-rates', [EegStatsController::class, 'getFocusRates']);
});
Route::middleware('auth:sanctum')->group(function() {
    // Ruta za čuvanje sirovog EEG signala
    Route::post('/eeg/raw-reading', [EegReadingController::class, 'store']);
});