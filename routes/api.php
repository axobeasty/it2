<?php

use Carbon\Carbon;
use App\Http\Controllers\MobileApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/current-time', function () {
    Carbon::setLocale('ru');
    $date = Carbon::now()->addHours(3)->translatedFormat('d F Y, H:i');
    return $date;
});

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileApiController::class, 'login']);
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::get('/me', [MobileApiController::class, 'me']);
    Route::get('/schedule', [MobileApiController::class, 'schedule']);
});
