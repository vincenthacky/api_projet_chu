<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthController extends Controller
{
    //

    public function login(Request $request)
    {
        // Validation de base
        $request->validate([
            'identifiant' => 'required|string', // peut être email ou téléphone
            'mot_de_passe' => 'required|string',
        ]);

        $identifiant = $request->identifiant;
        $motDePasse = $request->mot_de_passe;

        // Chercher par email OU téléphone
        $user = Utilisateur::where('email', $identifiant)
            ->orWhere('telephone', $identifiant)
            ->first();

        // Vérification
        if (!$user || !Hash::check($motDePasse, $user->mot_de_passe)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        // Génération du token JWT
        $token = JWTAuth::fromUser($user);

        // Mettre à jour la dernière connexion
        $user->derniere_connexion = now();
        $user->save();

        return response()->json([
            'status' => 'success',
            'user'   => $user,
            'token'  => $token,
        ]);
    }



     /**
     * 🚪 Déconnexion (logout)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['status' => 'success', 'message' => 'Déconnecté avec succès']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Erreur lors de la déconnexion'], 500);
        }
    }

    /**
     * 👤 Récupérer l’utilisateur connecté
     */
    public function me()
    {
        return response()->json(JWTAuth::parseToken()->authenticate());
    }

    
}
