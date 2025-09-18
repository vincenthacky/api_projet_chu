<?php

namespace App\Http\Controllers\Api;


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
            $user->load('photoProfil','cni','carteProfessionnelle','ficheSouscription');

            $data = [
                'user'  => $user,
                'token' => $token,
            ];

            return $this->responseSuccess($data, "Connexion réussie");
        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la connexion : " . $e->getMessage(), 500);
        }
    }


    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'ancien_mot_de_passe' => 'required|string',
                'nouveau_mot_de_passe' => 'required|string|min:6|confirmed', 
                // ⚠️ nécessite que le front envoie aussi "nouveau_mot_de_passe_confirmation"
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->responseError("Utilisateur non authentifié", 401);
            }

            if (!Hash::check($request->ancien_mot_de_passe, $user->mot_de_passe)) {
                return $this->responseError("L’ancien mot de passe est incorrect", 400);
            }

            // Vérification que les deux nouveaux mots de passe correspondent
            if ($request->nouveau_mot_de_passe !== $request->nouveau_mot_de_passe_confirmation) {
                return $this->responseError("Les deux nouveaux mots de passe ne correspondent pas", 400);
            }

            $user->mot_de_passe = Hash::make($request->nouveau_mot_de_passe);
            $user->save();

            return $this->responseSuccessMessage( "Mot de passe modifié avec succès. Veuillez utiliser le nouveau mot de passe pour vos prochaines connexions.");
        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la modification du mot de passe : " . $e->getMessage(), 500);
        }
    }





    /**
     * 🚪 Déconnexion (logout)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->responseSuccessMessage("Déconnexion effectuée avec succès");
        } catch (JWTException $e) {
            return $this->responseError("Erreur lors de la déconnexion", 500);
        }
    }

    /**
     * 👤 Récupérer l’utilisateur connecté
     */
   public function me()
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        $user->load(['cni', 'carteProfessionnelle', 'ficheSouscription', 'photoProfil']);
        return $this->responseSuccess($user, "Utilisateur connecté");
    } catch (\Exception $e) {
        return $this->responseError("Impossible de récupérer l'utilisateur connecté : " . $e->getMessage(), 401);
    }
}



    
}
