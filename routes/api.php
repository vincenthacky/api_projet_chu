<?php

use App\Http\Controllers\Api\PlanPaiementController;
use App\Http\Controllers\Api\ReclamationController;
use App\Http\Controllers\Api\TypeRecompenseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SouscriptionController;
use App\Http\Controllers\Api\StatutReclamationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EvenementController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RecompenseController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\TerrainController;
use App\Http\Controllers\Api\UtilisateurController;

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

Route::middleware('type:superAdmin,admin,user')->group(function () {

    Route::get('souscriptions/demandes', [SouscriptionController::class, 'indexDemandes']); 
    Route::get('souscriptions/demandes/utilisateur', [SouscriptionController::class, 'indexDemandesUtilisateur']); 
    Route::post('souscriptions/demandes', [SouscriptionController::class, 'storeDemande']); 
    Route::patch('souscriptions/demandes/{id}/changer-statut', [SouscriptionController::class, 'changerStatutDemande']); 
    Route::patch('/recompenses/{id}/statut', [RecompenseController::class, 'updateStatut']);
    Route::get('/utilisateurs-souscriptions', [UtilisateurController::class, 'indexWithSouscriptions']);

    Route::get('souscriptions/utilisateur', [SouscriptionController::class, 'indexUtilisateur']);
    Route::get('reclamations/utilisateur', [ReclamationController::class, 'indexUtilisateur']);
    Route::get('recompenses/utilisateur', [RecompenseController::class, 'indexUtilisateur']);
    Route::get('paiements/utilisateur', [PlanPaiementController::class, 'indexUtilisateur']);
    Route::get('documents/utilisateur', [DocumentController::class, 'indexUtilisateur']);
    Route::get('terrains/utilisateur', [TerrainController::class, 'indexUtilisateur']);


    Route::apiResource('souscriptions', SouscriptionController::class);
    Route::apiResource('utilisateurs', UtilisateurController::class);
    Route::apiResource('paiements', PlanPaiementController::class);
    Route::apiResource('evenements', EvenementController::class);
    Route::apiResource('recompenses', RecompenseController::class);
    Route::apiResource('type-recompenses', TypeRecompenseController::class);
    
    Route::apiResource('reclamations', ReclamationController::class);
    Route::apiResource('documents', DocumentController::class);
    Route::apiResource('terrains', TerrainController::class);
    Route::apiResource('statutreclamation', StatutReclamationController::class);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('register', [RegisterController::class, 'register']);
});





























