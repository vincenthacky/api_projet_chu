<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

class PasswordResetController extends Controller
{
    /**
     * 🔑 Envoyer un token de réinitialisation
     */
    public function sendResetToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:Utilisateur,email',
        ]);

        $user = Utilisateur::where('email', $request->email)->first();
        
        // Générer un token unique
        $user->token_reset = Str::random(60);
        $user->token_expiration = now()->addHour(); // valable 1 heure
        $user->save();

        try {
            // Envoyer l'email de réinitialisation
            Mail::to($user->email)->send(new ResetPasswordMail($user, $user->token_reset));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse email.',
                'email_sent_to' => $user->email,
                'expires_at' => $user->token_expiration->format('Y-m-d H:i:s'),
            ], 200);
            
        } catch (\Exception $e) {
            // En cas d'erreur d'envoi d'email, nettoyer le token
            $user->token_reset = null;
            $user->token_expiration = null;
            $user->save();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 🔄 Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token_reset' => 'required|string|exists:Utilisateur,token_reset',
            'mot_de_passe' => 'required|string|min:6|confirmed',
        ]);

        $user = Utilisateur::where('token_reset', $request->token_reset)->first();

        // Vérifier l’expiration du token
        if (!$user || $user->token_expiration < now()) {
            return response()->json(['error' => 'Token expiré ou invalide'], 400);
        }

        // Mettre à jour le mot de passe
        $user->mot_de_passe = Hash::make($request->mot_de_passe);
        $user->token_reset = null;
        $user->token_expiration = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Mot de passe réinitialisé avec succès',
        ]);
    }
}
