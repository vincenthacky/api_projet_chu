<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\UserSession;
use App\Models\SecurityAlert;
use App\Services\SecurityDetectionService;
use App\Services\SecurityAlertService;


class AuthController extends Controller
{
    //

     protected $securityDetection;
    protected $alertService;

    public function __construct(SecurityDetectionService $securityDetection, SecurityAlertService $alertService)
    {
        $this->securityDetection = $securityDetection;
        $this->alertService = $alertService;
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'identifiant' => 'required|string',
                'mot_de_passe' => 'required|string',
            ]);

            $identifiant = $request->identifiant;
            $motDePasse = $request->mot_de_passe;

            $user = Utilisateur::where('email', $identifiant)
                ->orWhere('telephone', $identifiant)
                ->first();

            if (!$user || !Hash::check($motDePasse, $user->mot_de_passe)) {
                return $this->responseError("Identifiant ou mot de passe incorrect", 401);
            }

            if ($user->statut_utilisateur !== Utilisateur::STATUT_ACTIF) {
                return $this->responseError("Votre compte est inactif ou suspendu. Veuillez contacter l'administrateur.", 403);
            }

            // ✅ ANALYSE DE SÉCURITÉ
            $securityAnalysis = $this->securityDetection->analyzeLoginAttempt($user, $request);

            // Générer le token JWT
            $token = JWTAuth::fromUser($user);
            $jwtId = Str::uuid();

            // Données de session
            $sessionData = [
                'user_id' => $user->id_utilisateur,
                'jwt_id' => $jwtId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'device_fingerprint' => hash('sha256', $request->header('User-Agent')),
                'country' => $securityAnalysis['location_info']['country'],
                'city' => $securityAnalysis['location_info']['city'],
                'is_trusted' => !$securityAnalysis['is_suspicious'], 
                'last_activity' => now(),
                'expires_at' => now()->addDays(7), 
            ];

            // Créer la session
            $userSession = UserSession::create($sessionData);

            // ✅ GESTION DES ALERTES DE SÉCURITÉ
            if ($this->alertService->shouldSendAlert($user, $securityAnalysis)) {
                $this->alertService->createAndSendAlert($user, $securityAnalysis, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'token' => $token
                ]);
            }

            // Nettoyer les anciennes sessions expirées
            $this->cleanupExpiredSessions($user->id_utilisateur);

            // Mettre à jour l'utilisateur
            $user->derniere_connexion = now();
            $user->save();
            $user->load('photoProfil', 'cni', 'carteProfessionnelle', 'ficheSouscription');

            $data = [
                'user' => $user,
                'token' => $token,
                
            ];

            return $this->responseSuccess($data, "Connexion réussie");

        } catch (\Exception $e) {
            Log::error('Erreur login: ' . $e->getMessage());
            return $this->responseError("Erreur lors de la connexion", 500);
        }
    }

    public function trustDevice(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $jwtId = JWTAuth::getJWTProvider()->decode(JWTAuth::getToken())['jti'] ?? null;

            if ($jwtId) {
                UserSession::where('jwt_id', $jwtId)
                    ->where('user_id', $user->id_utilisateur)
                    ->update(['is_trusted' => true]);

                return $this->responseSuccess(null, "Appareil marqué comme fiable");
            }

            return $this->responseError("Session non trouvée", 404);
        } catch (\Exception $e) {
            return $this->responseError("Erreur", 500);
        }
    }

    public function acknowledgeAlert(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $alertId = $request->alert_id;

            $alert = SecurityAlert::where('id', $alertId)
                ->where('user_id', $user->id_utilisateur)
                ->first();

            if (!$alert) {
                return $this->responseError("Alerte non trouvée", 404);
            }

            $alert->update([
                'is_acknowledged' => true,
                'acknowledged_at' => now()
            ]);

            return $this->responseSuccess(null, "Alerte acknowlegded");
        } catch (\Exception $e) {
            return $this->responseError("Erreur", 500);
        }
    }

    private function cleanupExpiredSessions($userId)
    {
        UserSession::where('user_id', $userId)
            ->where('expires_at', '<', now())
            ->delete();
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
