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
                return response()->json(['status' => 'Token invalide'], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json(['status' => 'Token expiré'], 401);
            } else {
                return response()->json(['status' => 'Token manquant'], 401);
            }
        }

        // Vérifie si le type correspond
        if (!in_array($user->type, $types)) {
            return response()->json(['status' => 'Non autorisé'], 403);
        }

        return $next($request);
    }
}
