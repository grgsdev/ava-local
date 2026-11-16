<?php

// Em routes/system.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Monolito\System\RoleController;
use App\Http\Controllers\Monolito\System\GeneratorController;
use App\Http\Controllers\Monolito\System\LogViewerController;
use App\Http\Controllers\Monolito\System\CommandLogController;
use App\Http\Controllers\Monolito\System\PermissionController;
use App\Http\Controllers\Monolito\System\ActivityLogController;
use App\Http\Controllers\Monolito\System\DeletedRecordController;
use App\Http\Controllers\Monolito\System\SystemDeveloperController;

Route::name('system.')->prefix('system')->group(function () {
    Route::get('/', function () {
        return redirect()->route('system.permissions.index');
    })->name('index');
    Route::resource('permissions', PermissionController::class)->except(['show']);
    Route::get('/permissions/selection', [PermissionController::class, 'selection'])->name('permissions.selection');
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::get('/roles/selection', [RoleController::class, 'selection'])->name('roles.selection');
    Route::resource('users', SystemDeveloperController::class);
    Route::get('/generator/{model}/export/pdf', [GeneratorController::class, 'exportPdf'])->name('generator.export.pdf');
    Route::resource('generator', GeneratorController::class);
    Route::post('/generator/{model}/run', [GeneratorController::class, 'runGenerator'])->name('generator.run');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])
        ->name('activity-log.index');
    Route::get('logs', [LogViewerController::class, 'index'])->name('logs.index');
    Route::get('logs/{date}', [LogViewerController::class, 'show'])->name('logs.show');
    Route::delete('logs/{date}', [LogViewerController::class, 'destroy'])->name('logs.destroy');
    Route::get('/command-log', [CommandLogController::class, 'index'])
        ->name('command-log.index');
    Route::get('/command-log/{id}', [CommandLogController::class, 'show'])
        ->name('command-log.show');
    Route::delete('/command-log/{id}', [CommandLogController::class, 'destroy'])
        ->name('command-log.delete');
    Route::get('/deleted-records', [DeletedRecordController::class, 'index'])
        ->name('deleted-records.index');
    Route::get('/deleted-records/{id}', [DeletedRecordController::class, 'show'])
        ->name('deleted-records.show');
    Route::delete('/deleted-records/{id}', [DeletedRecordController::class, 'destroy'])
        ->name('deleted-records.delete');
    Route::post('/deleted-records/{id}', [DeletedRecordController::class, 'recoverData'])
        ->name('deleted-records.recover');
});
