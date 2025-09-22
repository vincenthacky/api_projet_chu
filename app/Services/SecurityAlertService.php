<?php

namespace App\Services;
use Exception;
use App\Models\SecurityAlert;
use Illuminate\Support\Facades\Mail;
use App\Mail\SecurityAlertMail;
use Illuminate\Support\Facades\Log;




class SecurityAlertService
{
    public function shouldSendAlert($user, $analysisResult)
    {
        if (!$analysisResult['is_suspicious'] && !$analysisResult['is_new_device']) {
            return false;
        }

        // Vérifier s'il y a déjà une alerte non-acknowlegded récente
        $recentAlert = SecurityAlert::where('user_id', $user->id_utilisateur)
            ->where('alert_type', $analysisResult['is_suspicious'] ? 
                SecurityAlert::TYPE_SUSPICIOUS_LOGIN : 
                SecurityAlert::TYPE_NEW_DEVICE)
            ->where('is_acknowledged', false)
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($recentAlert) {
            // Ne pas envoyer d'alerte si une alerte similaire non-acknowlegded existe
            return false;
        }

        // Pour les nouvelles devices, envoyer seulement si c'est la première fois
        if ($analysisResult['is_new_device'] && !$analysisResult['is_suspicious']) {
            $existingNewDeviceAlerts = SecurityAlert::where('user_id', $user->id_utilisateur)
                ->where('alert_type', SecurityAlert::TYPE_NEW_DEVICE)
                ->where('created_at', '>', now()->subDays(7))
                ->count();

            // Limiter les alertes "nouveau device" à 1 par semaine
            if ($existingNewDeviceAlerts >= 1) {
                return false;
            }
        }

        return true;
    }

    public function createAndSendAlert($user, $analysisResult, $sessionData)
    {
        $alertType = $analysisResult['is_suspicious'] ? 
            SecurityAlert::TYPE_SUSPICIOUS_LOGIN : 
            SecurityAlert::TYPE_NEW_DEVICE;

        $alert = SecurityAlert::create([
            'user_id' => $user->id_utilisateur,
            'alert_type' => $alertType,
            'alert_data' => [
                'ip_address' => $sessionData['ip'],
                'user_agent' => $sessionData['user_agent'],
                'location' => $analysisResult['location_info'],
                'risk_level' => $analysisResult['risk_level'],
                'reasons' => $analysisResult['reasons'],
                'timestamp' => now()->toISOString(),
                'token' => $sessionData['token'] ?? null
            ]
        ]);

        // Envoyer l'email d'alerte
        $this->sendSecurityAlert($user, $alert);

        return $alert;
    }

    private function sendSecurityAlert($user, $alert)
    {
        try {
            Mail::to($user->email)->send(new SecurityAlertMail($user, $alert));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email sécurité: ' . $e->getMessage());
        }
    }
}