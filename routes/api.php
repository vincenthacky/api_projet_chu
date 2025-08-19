<?php

use App\Http\Controllers\Api\PlanPaiementController;
use App\Http\Controllers\Api\ReclamationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SouscriptionController;
use App\Http\Controllers\Api\StatutReclamationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;

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




// ✅ Route RESTful avec apiResource
Route::get('souscriptions/utilisateur', [SouscriptionController::class, 'indexUtilisateur']);
Route::apiResource('souscriptions', SouscriptionController::class);



Route::apiResource('paiements', PlanPaiementController::class);
Route::apiResource('reclamations', ReclamationController::class);
Route::apiResource('statutreclamation', StatutReclamationController::class);



Route::middleware('type:superAdmin,admin')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
    Route::post('register', [RegisterController::class, 'register']);

});


































// ✅ Si tu veux ajouter des actions personnalisées
// Route::controller(SouscriptionController::class)->group(function () {
//     Route::get('souscriptions/utilisateur/{id}', 'getByUtilisateur'); // toutes les souscriptions d’un utilisateur
//     Route::post('souscriptions/{id}/resilier', 'resilier'); // résiliation
//     Route::post('souscriptions/{id}/suspendre', 'suspendre'); // suspension
//     Route::post('souscriptions/{id}/reactiver', 'reactiver'); // réactivation
// });
