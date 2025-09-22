<?php

// ==================== MODÈLE UserSession (Migration) ====================
/*
Schema::create('user_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('utilisateurs');
    $table->string('jwt_id')->unique();
    $table->ipAddress('ip_address');
    $table->text('user_agent');
    $table->string('device_fingerprint')->nullable();
    $table->string('country')->nullable();
    $table->string('city')->nullable();
    $table->boolean('is_trusted')->default(false);
    $table->timestamp('last_activity');
    $table->timestamp('expires_at');
    $table->timestamps();
    
    $table->index(['user_id', 'is_trusted']);
    $table->index(['user_id', 'last_activity']);
});

Schema::create('security_alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('utilisateurs');
    $table->string('alert_type'); // 'suspicious_login', 'new_device'
    $table->json('alert_data');
    $table->boolean('is_acknowledged')->default(false);
    $table->timestamp('acknowledged_at')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'alert_type', 'is_acknowledged']);
});
*/

// ==================== MODÈLE UserSession ====================


// ==================== SERVICE DE DÉTECTION DE SÉCURITÉ ====================

// ==================== SERVICE DE GESTION DES ALERTES ====================


// ==================== CONTRÔLEUR PRINCIPAL ====================
class AuthController extends Controller
{
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
                'user_id' => $user->id,
                'jwt_id' => $jwtId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'device_fingerprint' => hash('sha256', $request->header('User-Agent')),
                'country' => $securityAnalysis['location_info']['country'],
                'city' => $securityAnalysis['location_info']['city'],
                'is_trusted' => !$securityAnalysis['is_suspicious'], // Faire confiance si pas suspect
                'last_activity' => now(),
                'expires_at' => now()->addDays(7), // Token JWT expire après 7 jours
            ];

            // Créer la session
            $userSession = UserSession::create($sessionData);

            // ✅ GESTION DES ALERTES DE SÉCURITÉ
            if ($this->alertService->shouldSendAlert($user, $securityAnalysis)) {
                $this->alertService->createAndSendAlert($user, $securityAnalysis, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);
            }

            // Nettoyer les anciennes sessions expirées
            $this->cleanupExpiredSessions($user->id);

            // Mettre à jour l'utilisateur
            $user->derniere_connexion = now();
            $user->save();
            $user->load('photoProfil', 'cni', 'carteProfessionnelle', 'ficheSouscription');

            $data = [
                'user' => $user,
                'token' => $token,
                'session_info' => [
                    'is_new_device' => $securityAnalysis['is_new_device'],
                    'risk_level' => $securityAnalysis['risk_level'],
                    'trusted' => $userSession->is_trusted
                ]
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
                    ->where('user_id', $user->id)
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
                ->where('user_id', $user->id)
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
}

