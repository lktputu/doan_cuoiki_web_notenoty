<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::options('/{any}', function () {
    return response('', 204);
})->where('any', '.*');

Route::get('/health', [ApiController::class, 'health']);
Route::post('/register', [ApiController::class, 'register']);
Route::get('/activate/{token}', [ApiController::class, 'activate']);
Route::post('/login', [ApiController::class, 'login']);
Route::post('/forgot-password', [ApiController::class, 'forgotPassword']);
Route::get('/password-reset/{token}', [ApiController::class, 'resetPasswordPage']);
Route::post('/reset-password', [ApiController::class, 'resetPassword']);
Route::get('/change-password/confirm/{token}', [ApiController::class, 'confirmPasswordChange']);
Route::post('/change-password/complete', [ApiController::class, 'completePasswordChange']);
Route::post('/realtime/authorize', [ApiController::class, 'authorizeRealtime']);

Route::post('/logout', [ApiController::class, 'logout']);
Route::get('/me', [ApiController::class, 'me']);
Route::put('/profile', [ApiController::class, 'updateProfile']);
Route::post('/change-password', [ApiController::class, 'changePassword']);
Route::put('/preferences', [ApiController::class, 'updatePreferences']);
Route::get('/bootstrap', [ApiController::class, 'bootstrap']);

Route::get('/notes/{id}', [ApiController::class, 'showNote']);
Route::post('/notes', [ApiController::class, 'storeNote']);
Route::put('/notes/{id}', [ApiController::class, 'updateNote']);
Route::delete('/notes/{id}', [ApiController::class, 'destroyNote']);
Route::post('/notes/{id}/pin', [ApiController::class, 'togglePin']);
Route::post('/notes/{id}/unlock', [ApiController::class, 'unlockNote']);
Route::post('/notes/{id}/password', [ApiController::class, 'setNotePassword']);
Route::delete('/notes/{id}/password', [ApiController::class, 'disableNotePassword']);
Route::post('/notes/{id}/shares', [ApiController::class, 'shareNote']);
Route::put('/notes/{id}/shares/{shareId}', [ApiController::class, 'updateShare']);
Route::delete('/notes/{id}/shares/{shareId}', [ApiController::class, 'revokeShare']);

Route::post('/labels', [ApiController::class, 'storeLabel']);
Route::put('/labels/{id}', [ApiController::class, 'updateLabel']);
Route::delete('/labels/{id}', [ApiController::class, 'destroyLabel']);
