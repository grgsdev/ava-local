<?php
// Em routes/api_v1.php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PlatformSettingController;


Route::get('/', function () {
    return response()->json([
        'project_name' => config('app.name'), // Pega o nome do seu .env
        'api_version' => 'v1.0.0'
    ]);
});
// Endpoints Públicos (não precisam de token)

Route::get('/settings', [PlatformSettingController::class, 'index'])->name('api.v1.settings.index');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/verify',   [AuthController::class, 'verify']);
Route::post('/resent-verify',   [AuthController::class, 'resendVerificationCode']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot.password.email');
Route::post('/forgot-password/verify',   [AuthController::class, 'verifyForgotPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/user/delete', [AuthController::class, 'destroy'])->name('user.destroy');
});
