<?php

use App\Http\Controllers\Api\WahaWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', [WahaWebhookController::class, 'handle']);
