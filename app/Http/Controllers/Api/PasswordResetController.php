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
    // public function sendResetToken(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:Utilisateur,email',
    //     ]);

    //     $user = Utilisateur::where('email', $request->email)->first();
        
    //     // Générer un token unique
    //     $user->token_reset = Str::random(60);
    //     $user->token_expiration = now()->addHour(); // valable 1 heure
    //     $user->save();

    //     try {
    //         // Envoyer l'email de réinitialisation
    //         Mail::to($user->email)->send(new ResetPasswordMail($user, $user->token_reset));
            
    //          return $this->responseSuccess([
    //             'message' => 'Un email de réinitialisation a été envoyé à votre adresse email.',
    //             'email_sent_to' => $user->email,
    //             'expires_at' => $user->token_expiration->format('Y-m-d H:i:s'),
    //         ]);
            
    //     } catch (\Exception $e) {
    //         $user->token_reset = null;
    //         $user->token_expiration = null;
    //         $user->save();
            
    //         return $this->responseError(
    //             "Erreur lors de l'envoi de l'email. Veuillez réessayer.",
    //             config('app.debug') ? $e->getMessage() : null,
    //             500
    //         );
    //     }
    // }

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

            // Vérifier si l'email a vraiment été envoyé
            if (count(Mail::failures()) > 0) {
                // Annuler le token si échec
                $user->update([
                    'token_reset' => null,
                    'token_expiration' => null,
                ]);

                return $this->responseError(
                    "Échec de l'envoi de l'email. Veuillez réessayer.",
                    null,
                    500
                );
            }

            return $this->responseSuccess([
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse email.',
                'email_sent_to' => $user->email,
                'expires_at' => $user->token_expiration->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            // En cas d'erreur serveur ou SMTP
            $user->update([
                'token_reset' => null,
                'token_expiration' => null,
            ]);

            return $this->responseError(
                "Erreur lors de l'envoi de l'email. Veuillez réessayer.",
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }


    /**
     * 🔄 Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        try {
        // ✅ Validation des champs
        $request->validate([
            'token_reset'   => 'required|string|exists:Utilisateur,token_reset',
            'mot_de_passe'  => 'required|string|min:6|confirmed', 
            // Laravel attend un champ "mot_de_passe_confirmation"
        ]);

        $user = Utilisateur::where('token_reset', $request->token_reset)->first();
        if (!$user || $user->token_expiration < now()) {
            return $this->responseError("Token expiré ou invalide", null, 400);
        }

        // ✅ Mise à jour du mot de passe
        $user->mot_de_passe = Hash::make($request->mot_de_passe);
        $user->token_reset = null;
        $user->token_expiration = null;
        $user->save();

        return $this->responseSuccessMessage("Mot de passe réinitialisé avec succès");
    } catch (\Illuminate\Validation\ValidationException $e) {

        return $this->responseError("Erreur de validation", $e->errors(), 422);
    } catch (\Exception $e) {
        return $this->responseError("Erreur lors de la réinitialisation du mot de passe", $e->getMessage(), 500);
    }

    
    }
}
