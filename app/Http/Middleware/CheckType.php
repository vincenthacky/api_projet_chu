<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class CheckType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$types)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return $this->responseError("Token invalide", 401);
            } elseif ($e instanceof TokenExpiredException) {
                return $this->responseError("Token expiré", 401);
            } else {
                return $this->responseError("Token manquant", 401);
            }
        }

         // ✅ Vérifie si l’utilisateur est actif
        if ($user->statut_utilisateur !== \App\Models\Utilisateur::STATUT_ACTIF) {
            return $this->responseError("Compte inactif ou suspendu", 403);
        }

        // ✅ Vérifie si le type correspond
        if (!in_array($user->type, $types)) {
            return $this->responseError("Non autorisé", 403);
        }

        return $next($request);
    }

    /**
     * Réponse erreur standardisée
     */
    protected function responseError($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'status_code' => $code,
            'message' => $message,
        ], $code);
    }

    /**
     * Réponse succès simple avec message
     */
    protected function responseSuccessMessage($message, $code = 200)
    {
        return response()->json([
            'success' => true,
            'status_code' => $code,
            'message' => $message,
        ], $code);
    }
}
