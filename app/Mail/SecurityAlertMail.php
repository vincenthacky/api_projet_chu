<?php

namespace App\Mail;
use App\Models\SecurityAlert;
use Illuminate\Mail\Mailable;




class SecurityAlertMail extends Mailable
{
    public $user;
    public $alert;

    public function __construct($user, $alert)
    {
        $this->user = $user;
        $this->alert = $alert;
    }

    public function build()
    {
        $subject = $this->alert->alert_type === SecurityAlert::TYPE_SUSPICIOUS_LOGIN 
            ? 'Connexion suspecte détectée' 
            : 'Connexion depuis un nouvel appareil';

        return $this->subject($subject)
                    ->view('emails.security-alert')
                    ->with([
                        'userName' => $this->user->nom,
                        'alertData' => $this->alert->alert_data,
                        'alertType' => $this->alert->alert_type,
                        'trustUrl' => url('/api/trust-device'),
                        'changePasswordUrl' => url('/change-password')
                    ]);
    }

    
}