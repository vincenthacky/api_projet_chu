<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\DocumentService;
use Exception;

class UtilisateurController extends Controller
{
    /**
     * Récupérer tous les utilisateurs avec pagination et recherche.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = Utilisateur::with(['cni', 'carteProfessionnelle', 'ficheSouscription', 'photoProfil']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenom', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('telephone', 'like', "%{$search}%")
                      ->orWhere('matricule', 'like', "%{$search}%");
                });
            }

            $utilisateurs = $query->orderBy('date_inscription', 'desc')
                                  ->paginate($perPage);

            return $this->responseSuccessPaginate($utilisateurs, "Liste des utilisateurs");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des utilisateurs : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer uniquement les utilisateurs ayant au moins une souscription,
     * avec leurs souscriptions et plans de paiement
     */
    public function indexWithSouscriptions(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            // Charger uniquement les utilisateurs qui ont au moins une souscription
            $query = Utilisateur::with(['souscriptions.planpaiements'])
                ->whereHas('souscriptions');

            // Recherche
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
                });
            }

            // Pagination
            $utilisateurs = $query->orderBy('date_inscription', 'desc')
                                ->paginate($perPage);

            return $this->responseSuccessPaginate($utilisateurs, "Liste des utilisateurs ayant des souscriptions avec plans de paiement");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des utilisateurs : " . $e->getMessage(), 500);
        }
    }



    /**
     * Récupérer les infos de l'utilisateur connecté (via JWT).
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $this->responseSuccess($user, "Utilisateur connecté");
        } catch (Exception $e) {
            return $this->responseError("Impossible de récupérer l'utilisateur connecté : " . $e->getMessage(), 401);
        }
    }

    /**
     * Récupérer un utilisateur par son ID.
     */
    public function show($id)
    {
        try {
            $utilisateur = Utilisateur::findOrFail($id);
            return $this->responseSuccess($utilisateur, "Utilisateur trouvé");
        } catch (Exception $e) {
            return $this->responseError("Utilisateur introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un utilisateur.
     */
    public function store(Request $request,DocumentService $documentService)
    {
        DB::beginTransaction();
        try {
             $validator = Validator::make($request->all(), [
            'nom'          => 'required|string|max:100',
            'prenom'       => 'required|string|max:100',
            'email'        => 'nullable|string|email|max:150|unique:Utilisateur,email',
            'telephone'    => 'required|string|max:20|unique:Utilisateur,telephone',
            'mot_de_passe' => 'required|string|min:6',
            'poste'        => 'nullable|string|max:100',
            'service'      => 'nullable|string|max:100',
            'type'         => 'nullable|string|max:40',

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
            DB::commit();

            return $this->responseSuccessMessage( "Utilisateur créé avec succès", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la création de l'utilisateur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour un utilisateur.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $utilisateur = Utilisateur::findOrFail($id);

            $request->validate([
                'nom'        => 'sometimes|string|max:100',
                'prenom'     => 'sometimes|string|max:100',
                'email'      => 'sometimes|email|unique:Utilisateur,email,' . $utilisateur->id_utilisateur . ',id_utilisateur',
                'telephone'  => 'sometimes|string|max:20',
                'type'       => 'sometimes|in:user,admin',
                'statut_utilisateur' => 'sometimes|in:actif,suspendu,inactif',
            ]);

            $data = $request->all();

            $utilisateur->update($data);

            DB::commit();

            return $this->responseSuccessMessage("Utilisateur mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la mise à jour de l'utilisateur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un utilisateur.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $utilisateur = Utilisateur::findOrFail($id);
            $utilisateur->delete();

            DB::commit();

            return $this->responseSuccessMessage("Utilisateur supprimé avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la suppression de l'utilisateur : " . $e->getMessage(), 500);
        }
    }
}
