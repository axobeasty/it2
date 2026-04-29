<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PasswordManagerController;
use App\Http\Controllers\PortfolioConfirmationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupScheduleController;
use App\Http\Controllers\ScheduleConstructorSettingsController;
use App\Http\Controllers\AcademicStructureController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StudentTestingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WikiController;
use App\Models\Notifs;
use Illuminate\Support\Facades\Route;

Route::get('/','App\Http\Controllers\AuthController@auth');
//Route::get('/test','App\Http\Controllers\TestController@test');
Route::get('/logout','App\Http\Controllers\AuthController@logout');
Route::get('/auth','App\Http\Controllers\AuthController@notallowed');
Route::post('/auth','App\Http\Controllers\AuthController@login')->middleware('throttle:login-web');
Route::get('/employees/{id}/activate/{code}','App\Http\Controllers\EmployeeController@activateEmployee');

Route::get('/password/forgot', [PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.forgot.send');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset')->where('token', '[a-fA-F0-9]{64}');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.reset.submit');

Route::middleware('page.access')->group(function () {
Route::get('/dashboard','App\Http\Controllers\AuthController@dashboard');
Route::get('/teachers','App\Http\Controllers\AuthController@teacher');
Route::get('/teachers/faculties', [AcademicStructureController::class, 'faculties']);
Route::post('/teachers/faculties/create', [AcademicStructureController::class, 'facultiesCreate']);
Route::post('/teachers/faculties/{id}/edit', [AcademicStructureController::class, 'facultiesEdit']);
Route::delete('/teachers/faculties/{id}/delete', [AcademicStructureController::class, 'facultiesDelete']);
Route::get('/teachers/chairs', [AcademicStructureController::class, 'chairs']);
Route::post('/teachers/chairs/create', [AcademicStructureController::class, 'chairsCreate']);
Route::post('/teachers/chairs/{id}/edit', [AcademicStructureController::class, 'chairsEdit']);
Route::delete('/teachers/chairs/{id}/delete', [AcademicStructureController::class, 'chairsDelete']);
Route::get('/students','App\Http\Controllers\AuthController@student');

Route::get('/employees','App\Http\Controllers\EmployeeController@index');
Route::post('/employees/new','App\Http\Controllers\EmployeeController@newEmployee');
Route::get('/employees/new','App\Http\Controllers\EmployeeController@notallowed');
Route::delete('/employees/delete/{id}','App\Http\Controllers\EmployeeController@delete');
Route::post('/employees/edit/{id}','App\Http\Controllers\EmployeeController@edit');
Route::get('/employees/edit/{id}','App\Http\Controllers\EmployeeController@notallowed');
Route::patch('/employees/deactivate/{id}','App\Http\Controllers\EmployeeController@deactivate');
Route::patch('/employees/activate/{id}','App\Http\Controllers\EmployeeController@activate');

Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles/create', [RoleController::class, 'create']);
Route::post('/roles/{id}/edit', [RoleController::class, 'update']);
Route::delete('/roles/{id}/delete', [RoleController::class, 'delete']);
Route::get('/groups', [GroupController::class, 'index']);
Route::post('/groups/create', [GroupController::class, 'create']);
Route::post('/groups/{id}/edit', [GroupController::class, 'update']);
Route::delete('/groups/{id}/delete', [GroupController::class, 'delete']);
Route::get('/groups/{id}/print-students', [GroupController::class, 'printStudents']);
Route::post('/groups/{id}/assign-students', [GroupController::class, 'assignStudents']);
Route::delete('/groups/students/{id}/detach', [GroupController::class, 'detachStudent']);

Route::get('/schedule', [GroupScheduleController::class, 'mySchedule'])->name('schedule.my');
Route::get('/schedule/teacher', [GroupScheduleController::class, 'teacherSchedule'])->name('schedule.teacher');
Route::get('/schedule/constructor', [GroupScheduleController::class, 'constructor'])->name('schedule.constructor');
Route::post('/schedule/entries', [GroupScheduleController::class, 'store'])->name('schedule.entries.store');
Route::post('/schedule/entries/{id}/edit', [GroupScheduleController::class, 'update'])->name('schedule.entries.update');
Route::delete('/schedule/entries/{id}/delete', [GroupScheduleController::class, 'delete'])->name('schedule.entries.delete');
Route::post('/schedule/copy-week', [GroupScheduleController::class, 'copyWeek'])->name('schedule.copy-week');
Route::post('/schedule/recalculate-week', [GroupScheduleController::class, 'recalculateWeek'])->name('schedule.recalculate-week');
Route::get('/schedule/constructor/settings', [ScheduleConstructorSettingsController::class, 'index'])->name('schedule.constructor.settings');
Route::post('/schedule/constructor/settings', [ScheduleConstructorSettingsController::class, 'save'])->name('schedule.constructor.settings.save');
Route::post('/schedule/constructor/subjects', [ScheduleConstructorSettingsController::class, 'storeSubject'])->name('schedule.subjects.store');
Route::delete('/schedule/constructor/subjects/{id}/delete', [ScheduleConstructorSettingsController::class, 'deleteSubject'])->name('schedule.subjects.delete');

Route::get('/settings','App\Http\Controllers\SettingsController@index');
Route::get('/settings/authenticate','App\Http\Controllers\SettingsController@authenticate');
Route::get('/settings/general','App\Http\Controllers\SettingsController@general');
Route::get('/settings/database','App\Http\Controllers\SettingsController@database');
Route::patch('/settings/general/site/disable','App\Http\Controllers\SettingsController@disable');
Route::patch('/settings/general/site/enable','App\Http\Controllers\SettingsController@enable');
Route::post('/settings/save','App\Http\Controllers\SettingsController@save');
Route::post('/settings/database/save','App\Http\Controllers\SettingsController@saveDatabase');
Route::post('/settings/database/save-remote-draft','App\Http\Controllers\SettingsController@saveRemoteDraft');
Route::post('/settings/database/activate-profile','App\Http\Controllers\SettingsController@activateDatabaseProfile');
Route::post('/settings/database/test-connection','App\Http\Controllers\SettingsController@testRemoteDatabaseConnection');
Route::post('/settings/database/dry-run-init','App\Http\Controllers\SettingsController@dryRunRemoteInitialization');
Route::post('/settings/database/initialize','App\Http\Controllers\SettingsController@initializeRemoteDatabase');
Route::post('/settings/database/initialize-stream','App\Http\Controllers\SettingsController@initializeRemoteDatabaseStream');
Route::post('/settings/database/migrate','App\Http\Controllers\SettingsController@migrateDatabase');
Route::post('/settings/database/migrate-stream','App\Http\Controllers\SettingsController@migrateDatabaseStream');
Route::get('/settings/email','App\Http\Controllers\SettingsController@email');
Route::post('/settings/email/test','App\Http\Controllers\SettingsController@sendTestEmail');
Route::post('/settings/git/check-updates','App\Http\Controllers\SettingsController@checkGitUpdates');
Route::post('/settings/git/pull-updates','App\Http\Controllers\SettingsController@pullGitUpdates');
Route::post('/settings/git/deploy-ref','App\Http\Controllers\SettingsController@saveDeployRef');

Route::get('/inv','App\Http\Controllers\InventoryController@index');
Route::get('/inv/manage','App\Http\Controllers\InventoryController@manage');
Route::get('/inv/types','App\Http\Controllers\InventoryController@types');
Route::post('/inv/assign','App\Http\Controllers\InventoryController@assign');
Route::post('/inv/unassign/{id}','App\Http\Controllers\InventoryController@unassign');
Route::post('/inv/unassign-all/{employeeId}','App\Http\Controllers\InventoryController@unassignAll');
Route::post('/inv/reassign/{id}','App\Http\Controllers\InventoryController@reassign');
Route::get('/inv/export','App\Http\Controllers\InventoryController@export');
Route::get('/inv/print','App\Http\Controllers\InventoryController@print');
Route::get('/inv/departments/manage','App\Http\Controllers\InventoryController@departments');
Route::delete('/inv/departments/delete/{id}','App\Http\Controllers\InventoryController@dep_delete');
Route::post('/inv/departments/create','App\Http\Controllers\InventoryController@dep_create');
Route::post('/inv/departments/{id}/edit','App\Http\Controllers\InventoryController@dep_edit');

Route::get('/orders','App\Http\Controllers\OrdersController@my');
Route::get('/orders/categories','App\Http\Controllers\OrdersController@categories');

Route::patch('/orders/{id}/status/set/{code}','App\Http\Controllers\OrdersController@UpdateStatus');

Route::post('/orders/categories/create','App\Http\Controllers\OrdersController@c_category');
Route::get('/orders/categories/create','App\Http\Controllers\OrdersController@notallowed');

Route::delete('/orders/categories/delete/{id}','App\Http\Controllers\OrdersController@d_category');

Route::post('/orders/create', [OrdersController::class, 'create'])->name('orders.create');
Route::get('/orders/create', [OrdersController::class, 'notallowed'])->name('orders.create');
Route::get('/orders/my','App\Http\Controllers\OrdersController@my');
Route::get('/orders/administration','App\Http\Controllers\OrdersController@administration');

Route::post('/task/add','App\Http\Controllers\TaskController@add_task');
Route::get('/task/add','App\Http\Controllers\TaskController@notallowed');

Route::post('/task/done','App\Http\Controllers\TaskController@add_task');
Route::get('/task/done','App\Http\Controllers\TaskController@notallowed');

Route::delete('/task/delete/{id}', [TaskController::class, 'delete'])->name('task.delete');
//Route::post('/task/done','App\Http\Controllers\TaskController@add_task');
//Route::get('/task/done','App\Http\Controllers\TaskController@notallowed');
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
Route::post('/profile/notifications', [ProfileController::class, 'updateEmailNotifications']);
Route::get('/profile/portfolio','App\Http\Controllers\ProfileController@p_show');
Route::post('/portfolio/add', 'App\Http\Controllers\ProfileController@p_add');
Route::get('/portfolio/attachment/{portfolio}', [ProfileController::class, 'portfolioFile'])
    ->whereNumber('portfolio')
    ->name('portfolio.file');

Route::post('/notifications/mark-all-read', [NotificationController::class,'makeread']);

Route::get('/wiki', [WikiController::class, 'index'])->name('wiki.index');
Route::get('/wiki/create', [WikiController::class, 'create'])->name('wiki.create');
Route::post('/wiki/store', [WikiController::class, 'store'])->name('wiki.store');
Route::get('/wiki/{slug}/edit', [WikiController::class, 'edit'])->where('slug', WikiController::SLUG_REGEX)->name('wiki.edit');
Route::patch('/wiki/{slug}', [WikiController::class, 'update'])->where('slug', WikiController::SLUG_REGEX)->name('wiki.update');
Route::delete('/wiki/{slug}', [WikiController::class, 'destroy'])->where('slug', WikiController::SLUG_REGEX)->name('wiki.destroy');
Route::get('/wiki/{slug}', [WikiController::class, 'show'])->where('slug', WikiController::SLUG_REGEX)->name('wiki.show');

Route::get('/passwords', [PasswordManagerController::class, 'index']);
Route::post('/passwords/create', [PasswordManagerController::class, 'store']);
Route::post('/passwords/{id}/reveal', [PasswordManagerController::class, 'reveal']);
Route::delete('/passwords/{id}/delete', [PasswordManagerController::class, 'destroy']);

Route::get('/tests', [StudentTestingController::class, 'studentIndex']);
Route::get('/tests/admin', [StudentTestingController::class, 'adminIndex']);
Route::post('/tests/admin/create', [StudentTestingController::class, 'store']);
Route::get('/tests/admin/{id}/edit', [StudentTestingController::class, 'edit'])->whereNumber('id');
Route::post('/tests/admin/{id}/update', [StudentTestingController::class, 'update'])->whereNumber('id');
Route::post('/tests/admin/{id}/toggle', [StudentTestingController::class, 'toggle']);
Route::get('/tests/stats/export', [StudentTestingController::class, 'statsExport']);
Route::get('/tests/stats/print', [StudentTestingController::class, 'statsPrint']);
Route::get('/tests/stats', [StudentTestingController::class, 'stats']);
Route::get('/tests/{id}/review', [StudentTestingController::class, 'review'])->whereNumber('id');
Route::get('/tests/{id}', [StudentTestingController::class, 'take'])->whereNumber('id');
Route::post('/tests/{id}/submit', [StudentTestingController::class, 'submit'])->whereNumber('id');

Route::get('/portfolio/confirm', [PortfolioConfirmationController::class, 'index'])->name('portfolio.confirm');
Route::post('/portfolio/confirm/{portfolio}/approve', [PortfolioConfirmationController::class, 'approve'])->whereNumber('portfolio')->name('portfolio.confirm.approve');
Route::post('/portfolio/confirm/{portfolio}/reject', [PortfolioConfirmationController::class, 'reject'])->whereNumber('portfolio')->name('portfolio.confirm.reject');

Route::get('/portfolio/types','App\Http\Controllers\ProfileController@stypes');
Route::post('/portfolio/types/add', 'App\Http\Controllers\ProfileController@type_add');
Route::delete('/portfolio/types/{id}/delete', 'App\Http\Controllers\ProfileController@type_delete');
Route::get('/portfolio/roles','App\Http\Controllers\ProfileController@sroles');
});
