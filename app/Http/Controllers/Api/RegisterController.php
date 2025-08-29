<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class RegisterController extends Controller
{
    //

    /**
     * 📌 Inscription (register)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'        => 'required|string|max:100',
            'prenom'     => 'required|string|max:100',
            'email'      => 'nullable|string|email|max:150|unique:Utilisateur,email',
            'telephone'  => 'required|string|max:20|unique:Utilisateur,telephone',
            'mot_de_passe' => 'required|string|min:6',
            'poste'      => 'nullable|string|max:100',
            'service'    => 'nullable|string|max:100',
            'type'    => 'nullable|string|max:40',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Utilisateur::create([
            'nom'                => $request->nom,
            'prenom'             => $request->prenom,
            'email'              => $request->email,
            'type'              => $request->type,
            'telephone'          => $request->telephone,
            'poste'              => $request->poste,
            'service'            => $request->service,
            'mot_de_passe'       => bcrypt($request->mot_de_passe),
            'date_inscription'   => now(),
            'statut_utilisateur' => Utilisateur::STATUT_ACTIF,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token
        ], 201);
    }

}
