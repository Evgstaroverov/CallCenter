<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use Illuminate\Support\Facades\Route;

// Маршруты авторизации
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Защищенные маршруты
Route::middleware('auth')->group(function () {
    Route::get('/', [HelloController::class, 'index']);
    
    // API маршруты
    Route::get('/chats', [HelloController::class, 'getChats']);
    Route::get('/messages/{chatId}', [HelloController::class, 'getMessages']);
    Route::post('/send-message', [HelloController::class, 'store']);
});