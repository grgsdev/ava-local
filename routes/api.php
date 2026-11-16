<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Webhook\RevenueCatWebhookController;

Route::get('/', function () {
    return response()->json([
        'project_name' => config('app.name'), // Pega o nome do seu .env
        'api_version' => 'v1.0.0'
    ]);
});

Route::prefix('v1')->name('api.v1.')->group(base_path('routes/api_v1.php'));
