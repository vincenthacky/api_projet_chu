<?php

namespace App\Services;
use App\Models\UserSession;
use Illuminate\Support\Facades\Http;
use Exception;


class SecurityDetectionService
{
    public function analyzeLoginAttempt($user, $request)
    {
        $currentIp = $request->ip();
        $currentUserAgent = $request->header('User-Agent');
        $deviceFingerprint = $this->generateDeviceFingerprint($request);

        // Récupérer les sessions existantes de l'utilisateur
        $existingSessions = UserSession::where('user_id', $user->id_utilisateur)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();

        $analysis = [
            'is_suspicious' => false,
            'is_new_device' => false,
            'risk_level' => 'low', // low, medium, high
            'reasons' => [],
            'location_info' => $this->getLocationInfo($currentIp)
        ];

        if ($existingSessions->isEmpty()) {
            // Premier login ou toutes les sessions ont expiré
            $analysis['is_new_device'] = true;
            $analysis['risk_level'] = 'low';
            $analysis['reasons'][] = 'Première connexion ou sessions expirées';
        } else {
            // Analyser les sessions existantes
            $analysis = $this->analyzeSuspiciousActivity(
                $existingSessions, 
                $currentIp, 
                $currentUserAgent, 
                $deviceFingerprint,
                $analysis
            );
        }

        return $analysis;
    }

    private function analyzeSuspiciousActivity($existingSessions, $currentIp, $currentUserAgent, $deviceFingerprint, $analysis)
    {
        $trustedSessions = $existingSessions->where('is_trusted', true);
        $hasMatchingSession = false;

        foreach ($existingSessions as $session) {
            if ($this->isSessionMatching($session, $currentIp, $currentUserAgent, $deviceFingerprint)) {
                $hasMatchingSession = true;
                break;
            }
        }

        if (!$hasMatchingSession) {
            $analysis['is_new_device'] = true;
            $analysis['reasons'][] = 'Nouvel appareil ou localisation';

            // Vérifier si c'est suspect
            if ($trustedSessions->isNotEmpty()) {
                $lastTrustedSession = $trustedSessions->first();
                
                // Connexion suspecte si :
                // 1. IP très différente géographiquement
                // 2. User agent complètement différent
                // 3. Connexion peu de temps après une autre session active
                
                if ($this->isGeographicallyDistant($lastTrustedSession, $analysis['location_info'])) {
                    $analysis['is_suspicious'] = true;
                    $analysis['risk_level'] = 'high';
                    $analysis['reasons'][] = 'Localisation géographique suspecte';
                }

                if (!$lastTrustedSession->isSimilarUserAgent($currentUserAgent)) {
                    $analysis['is_suspicious'] = true;
                    $analysis['risk_level'] = $analysis['risk_level'] === 'high' ? 'high' : 'medium';
                    $analysis['reasons'][] = 'Appareil complètement différent';
                }

                // Connexion simultanée depuis des endroits différents
                if ($lastTrustedSession->last_activity > now()->subMinutes(30)) {
                    $analysis['is_suspicious'] = true;
                    $analysis['risk_level'] = 'high';
                    $analysis['reasons'][] = 'Connexions simultanées détectées';
                }
            }
        }

        return $analysis;
    }

    private function isSessionMatching($session, $ip, $userAgent, $fingerprint)
    {
        return $session->ip_address === $ip || 
               $session->device_fingerprint === $fingerprint ||
               $session->isSimilarUserAgent($userAgent);
    }

    private function isGeographicallyDistant($session, $currentLocation)
    {
        if (!$session->country || !$currentLocation['country']) {
            return false;
        }

        // Si pays différents, c'est suspect
        if ($session->country !== $currentLocation['country']) {
            return true;
        }

        // Si même pays mais villes très éloignées
        if ($session->city && $currentLocation['city']) {
            return $session->city !== $currentLocation['city'];
        }

        return false;
    }

    private function generateDeviceFingerprint($request)
    {
        $components = [
            $request->header('User-Agent'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', $components));
    }

    private function getLocationInfo($ip)
    {
        try {
            // Utiliser un service de géolocalisation (exemple avec ipinfo.io)
            $response = Http::get("https://ipinfo.io/{$ip}/json");
            $data = $response->json();

            return [
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['country' => null, 'city' => null, 'region' => null];
        }
    }
}
