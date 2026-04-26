<?php

use Illuminate\Support\Facades\Route;

// SPA маршрут - все запросы направляем на Vue приложение
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$')->name('app');

