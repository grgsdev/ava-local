<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Monolito\Admin\RoleController;
use App\Http\Controllers\Monolito\Admin\UserController;
use App\Http\Controllers\Monolito\Admin\DashboardController;
use App\Http\Controllers\Monolito\Admin\ModerationController;
use App\Http\Controllers\Monolito\Admin\CensoredWordController;
use App\Http\Controllers\Monolito\Admin\NotificationController;
use App\Http\Controllers\Monolito\Admin\PlatformSettingController;
use App\Http\Controllers\Monolito\Admin\ScheduledNotificationController;

// Todas as rotas aqui terão o prefixo 'admin' e o nome 'admin.'
Route::name('admin.')->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('tenant')->name('dashboard');

    Route::get('/settings', [PlatformSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings/update', [PlatformSettingController::class, 'update'])->name('settings.update');

    Route::resource('censored-words', CensoredWordController::class)->only(['index', 'store', 'destroy']);
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
    Route::get('/moderation/{moderationCase}', [ModerationController::class, 'show'])->name('moderation.show');
    Route::put('/moderation/{moderationCase}', [ModerationController::class, 'update'])->name('moderation.update');

    Route::put('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.get.read');

    Route::put('/notifications/{notification}/mark_as_read', [NotificationController::class, 'markAsRead'])->name('notifications.mark.as.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy'); // <-- NOVA ROTA
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    //selections
    Route::get('/users/selection', [UserController::class, 'selection'])->name('users.selection');
    Route::get('/roles/selection', [RoleController::class, 'selection'])->name('roles.selection');
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleIsActive'])->name('users.toggleActive');
    Route::patch('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::patch('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');



    Route::resource('scheduled-notifications', ScheduledNotificationController::class)
        ->names('scheduled-notifications');
});
