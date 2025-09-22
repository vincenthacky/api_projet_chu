<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAlert extends Model
{
     const TYPE_SUSPICIOUS_LOGIN = 'suspicious_login';
    const TYPE_NEW_DEVICE = 'new_device';

    protected $fillable = [
        'user_id', 'alert_type', 'alert_data', 
        'is_acknowledged', 'acknowledged_at'
    ];

    protected $casts = [
        'alert_data' => 'array',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Utilisateur::class);
    }
}
