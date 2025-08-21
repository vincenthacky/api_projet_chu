<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Utilisateur;

class ResetPasswordMail extends Mailable 
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $resetUrl;

    public function __construct(Utilisateur $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
        
        // URL de votre frontend pour la réinitialisation
        $this->resetUrl = config('app.frontend_url', 'http://192.168.252.208:4200/authentification') 
            . '/reset-password?token=' . $token 
            . '&email=' . urlencode($user->email);

            
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address'),
            subject: 'Réinitialisation de votre mot de passe - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}