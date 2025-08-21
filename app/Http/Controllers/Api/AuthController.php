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
        try {
            $request->validate([
                'identifiant'   => 'required|string', 
                'mot_de_passe'  => 'required|string',
            ]);

            $identifiant = $request->identifiant;
            $motDePasse  = $request->mot_de_passe;
            $user = Utilisateur::where('email', $identifiant)
                ->orWhere('telephone', $identifiant)
                ->first();

            if (!$user || !Hash::check($motDePasse, $user->mot_de_passe)) {
                return $this->responseError("Identifiant ou mot de passe incorrect", 401);
            }

            $token = JWTAuth::fromUser($user);
            $user->derniere_connexion = now();
            $user->save();

            $data = [
                'user'  => $user,
                'token' => $token,
            ];

            return $this->responseSuccess($data, "Connexion réussie");
        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la connexion : " . $e->getMessage(), 500);
        }
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
