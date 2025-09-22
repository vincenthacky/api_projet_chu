<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DocumentService;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;



class RegisterController extends Controller
{
    //


    /**
 * 📌 Inscription (register)
 */
    public function register(Request $request, DocumentService $documentService)
    {
        $validator = Validator::make($request->all(), [
            'nom'          => 'required|string|max:100',
            'prenom'       => 'required|string|max:100',
            'email'        => 'nullable|string|email|max:150|unique:Utilisateur,email',
            'telephone'    => 'required|string|max:20|unique:Utilisateur,telephone',
            'mot_de_passe' => 'required|string|min:6',
            'poste'        => 'nullable|string|max:100',
            'service'      => 'nullable|string|max:100',
            'type'         => [
                'required',
                'string',
                'max:40',
                Rule::in(Utilisateur::TYPES_UTILISATEUR), // ✅ validation directe
            ],

            // 📂 Validation des fichiers
            'cni'                  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'carte_professionnel'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'fiche_souscription'   => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'photo_profil'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5Mo image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $est_administrateur = $request->type !== Utilisateur::TYPE_USER ? 1 : 0;

        // ✅ Création utilisateur
        $user = Utilisateur::create([
            'nom'                => $request->nom,
            'prenom'             => $request->prenom,
            'email'              => $request->email,
            'type'               => $request->type,
            'telephone'          => $request->telephone,
            'poste'              => $request->poste,
            'service'            => $request->service,
            'mot_de_passe'       => bcrypt($request->mot_de_passe),
            'date_inscription'   => now(),
            'statut_utilisateur' => Utilisateur::STATUT_ACTIF,
            'est_administrateur' => $est_administrateur,
        ]);

        // ✅ Upload des documents si présents
        $documents = [
            'cni'                 => 'CNI',
            'carte_professionnel' => 'Carte Professionnelle',
            'fiche_souscription'  => 'Fiche de Souscription',
            'photo_profil'        => 'Photo de Profil',
        ];

        foreach ($documents as $champ => $libelle) {
            if ($request->hasFile($champ)) {
                $documentService->store(
                    idSouscription: null, // tu peux mettre l’id si lié
                    libelleTypeDocument: $libelle,
                    options: [
                        'source_table'         => 'utilisateurs',
                        'source_id'            => $user->id_utilisateur,
                        'description_document' => "Document {$libelle} de l'utilisateur {$user->nom} {$user->prenom}",
                    ],
                    fichier: $request->file($champ)
                );
            }
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    

}
