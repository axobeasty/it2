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
    Route::post('/login', [MobileApiController::class, 'login'])->middleware('throttle:login-mobile');
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::get('/me', [MobileApiController::class, 'me']);
    Route::get('/schedule', [MobileApiController::class, 'schedule']);
    Route::get('/notifications', [MobileApiController::class, 'notifications']);
    Route::get('/test-stats', [MobileApiController::class, 'testStats']);
    Route::get('/wiki', [MobileApiController::class, 'wikiList']);
    Route::get('/wiki/{slug}', [MobileApiController::class, 'wikiShow'])->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
    Route::get('/orders/my', [MobileApiController::class, 'ordersMy']);
    Route::post('/orders/create', [MobileApiController::class, 'ordersCreate']);
    Route::patch('/orders/{id}/status/{code}', [MobileApiController::class, 'ordersSetStatus'])
        ->whereNumber('id')
        ->where('code', '[0-3]');
    Route::get('/orders/categories', [MobileApiController::class, 'ordersCategories']);
    Route::get('/inventory/my', [MobileApiController::class, 'inventoryMy']);
    Route::get('/inventory/manage', [MobileApiController::class, 'inventoryManage']);
    Route::get('/employees', [MobileApiController::class, 'employeesList']);
    Route::patch('/employees/{id}/active/{state}', [MobileApiController::class, 'employeesSetActive'])
        ->whereNumber('id')
        ->where('state', '[01]');
    Route::patch('/employees/{id}/assign', [MobileApiController::class, 'employeesAssign'])->whereNumber('id');
    Route::get('/roles', [MobileApiController::class, 'rolesList']);
    Route::post('/roles', [MobileApiController::class, 'rolesCreate']);
    Route::patch('/roles/{id}', [MobileApiController::class, 'rolesUpdate'])->whereNumber('id');
    Route::delete('/roles/{id}', [MobileApiController::class, 'rolesDelete'])->whereNumber('id');
    Route::get('/roles/{id}/permissions', [MobileApiController::class, 'rolesPermissions'])->whereNumber('id');
    Route::post('/roles/{id}/permissions', [MobileApiController::class, 'rolesPermissionsSave'])->whereNumber('id');
    Route::get('/groups', [MobileApiController::class, 'groupsList']);
    Route::post('/groups', [MobileApiController::class, 'groupsCreate']);
    Route::patch('/groups/{id}', [MobileApiController::class, 'groupsUpdate'])->whereNumber('id');
    Route::delete('/groups/{id}', [MobileApiController::class, 'groupsDelete'])->whereNumber('id');
    Route::get('/settings/general', [MobileApiController::class, 'settingsGeneral']);
    Route::post('/settings/general', [MobileApiController::class, 'settingsGeneralSave']);

    Route::get('/tests', [MobileApiController::class, 'testsList']);
    Route::post('/tests/{id}/session', [MobileApiController::class, 'testBegin'])->whereNumber('id');
    Route::post('/tests/{id}/submit', [MobileApiController::class, 'testSubmit'])->whereNumber('id');

    Route::get('/student-tests', [MobileApiController::class, 'testsList']);
    Route::post('/student-tests/{id}/session', [MobileApiController::class, 'testBegin'])->whereNumber('id');
    Route::post('/student-tests/{id}/submit', [MobileApiController::class, 'testSubmit'])->whereNumber('id');
});
