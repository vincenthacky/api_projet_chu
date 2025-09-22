<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Validator;


class PasswordResetController extends Controller
{
    

    public function sendResetToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $user = Utilisateur::where('email', $request->email)->first();

        if (!$user) {
            return $this->responseError("Aucun utilisateur trouvé avec cet email", 404);
        }

        // ✅ Vérifier si un token est déjà actif
        if ($user->token_reset && $user->token_expiration > now()) {
            return $this->responseError("Un lien de réinitialisation a déjà été envoyé. Vérifiez vos emails ou attendez son expiration.", 429);
        }

        // Générer un nouveau token
        $user->token_reset = Str::random(60);
        $user->token_expiration = now()->addHour(); // expire dans 60 min
        $user->save();


        // Envoyer l'email avec gestion des erreurs
        try {
            
            Mail::to($user->email)->send(new ResetPasswordMail($user, $user->token_reset));

            // Si on arrive ici, l'envoi a réussi. On retourne une réponse de succès.
            return $this->responseSuccess(
                [
                    'email_sent_to' => $user->email,
                    'expires_at' => $user->token_expiration->format('Y-m-d H:i:s'),
                ],
                'Un email de réinitialisation a été envoyé à votre adresse email.'
            );

        } catch (\Exception $e) {
           
            $user->update([
                'token_reset' => null,
                'token_expiration' => null,
            ]);

            $errorMessage = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
            if (config('app.debug')) {
                $errorMessage .= ' Détails : ' . $e->getMessage();
            }

            return $this->responseError($errorMessage, 500);
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
            ]);

            $user = Utilisateur::where('token_reset', $request->token_reset)->first();

            if (!$user || $user->token_expiration < now()) {
                return $this->responseError("Token expiré ou invalide", 400);
            }

            // ✅ Mise à jour du mot de passe
            $user->mot_de_passe = Hash::make($request->mot_de_passe);
            $user->token_reset = null;
            $user->token_expiration = null;
            $user->save();

            return $this->responseSuccessMessage("Mot de passe réinitialisé avec succès");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseError(json_encode($e->errors()), 422);
        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la réinitialisation du mot de passe : " . $e->getMessage(), 500);
        }
    }

}
