<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'user_id', 'jwt_id', 'ip_address', 'user_agent', 
        'device_fingerprint', 'country', 'city', 
        'is_trusted', 'last_activity', 'expires_at'
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Utilisateur::class);
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function isFromSameLocation($ip, $userAgent)
    {
        return $this->ip_address === $ip || 
               $this->isSimilarUserAgent($userAgent);
    }

    public function isSimilarUserAgent($userAgent)
    {
        // Comparaison basique des user agents (même navigateur/OS)
        $currentBrowser = $this->extractBrowser($this->user_agent);
        $newBrowser = $this->extractBrowser($userAgent);
        
        return $currentBrowser === $newBrowser;
    }

    private function extractBrowser($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Unknown';
    }
}
