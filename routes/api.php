<?php

use App\Http\Controllers\Api\PlanPaiementController;
use App\Http\Controllers\Api\ReclamationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SouscriptionController;
use App\Http\Controllers\Api\StatutReclamationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EvenementController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RecompenseController;
use App\Http\Controllers\Api\PasswordResetController;

/*
BYYYY**********

    ****  *      *********            ********        ****        ****   ****    ****.
    ****    *    *********            ****   *       ******       ****   ****    ****
    ****     *   ****                 **** ***      ********      ****   ****    ****
    ****      *  *********            ****         ***    ***     ****   ****    ****
    ****  * *    *********            ****        ****    ****      ******       *********.
    ****  *      *********            ****       ****      ****       ****        *********.
*/
// 🔹 auth route

Route::post('login', [AuthController::class, 'login']);
Route::post('/password/send-token', [PasswordResetController::class, 'sendResetToken']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::post('/password/update', [AuthController::class, 'updatePassword']);



// ✅ Route RESTful avec apiResource
Route::get('souscriptions/utilisateur', [SouscriptionController::class, 'indexUtilisateur']);
Route::apiResource('souscriptions', SouscriptionController::class);


Route::apiResource('paiements', PlanPaiementController::class);
Route::apiResource('evenements', EvenementController::class);
Route::apiResource('recompenses', RecompenseController::class);
Route::apiResource('reclamations', ReclamationController::class);
Route::apiResource('documents', DocumentController::class);
Route::apiResource('statutreclamation', StatutReclamationController::class);



Route::middleware('type:superAdmin,admin,user')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('register', [RegisterController::class, 'register']);
});





























