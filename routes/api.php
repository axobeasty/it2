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

/*
|--------------------------------------------------------------------------
| Мобильное приложение (IT-Master Android): префикс /api/mobile/…
| Тесты: GET/POST …/tests/… и дубликат …/student-tests/… (тот же контроллер).
|--------------------------------------------------------------------------
*/
Route::prefix('mobile')->group(function () {
    Route::get('/health', [MobileApiController::class, 'health']);
    Route::post('/login', [MobileApiController::class, 'login']);
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::get('/me', [MobileApiController::class, 'me']);
    Route::get('/schedule', [MobileApiController::class, 'schedule']);
    Route::get('/notifications', [MobileApiController::class, 'notifications']);
    Route::get('/test-stats', [MobileApiController::class, 'testStats']);

    Route::get('/tests', [MobileApiController::class, 'testsList']);
    Route::post('/tests/{id}/session', [MobileApiController::class, 'testBegin'])->whereNumber('id');
    Route::post('/tests/{id}/submit', [MobileApiController::class, 'testSubmit'])->whereNumber('id');

    Route::get('/student-tests', [MobileApiController::class, 'testsList']);
    Route::post('/student-tests/{id}/session', [MobileApiController::class, 'testBegin'])->whereNumber('id');
    Route::post('/student-tests/{id}/submit', [MobileApiController::class, 'testSubmit'])->whereNumber('id');
});
