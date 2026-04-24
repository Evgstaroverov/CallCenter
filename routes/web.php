<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelloController;

/*
Route::get('/', function () {
    return view('welcome');
});


*/

//Route::get('/hello', [HelloController::class, 'index']);

Route::get('/', function () {
    return view('hello'); 
});

Route::get('/telegram-messages', [HelloController::class, 'index']);

Route::post('/send-message', [HelloController::class, 'store']);

