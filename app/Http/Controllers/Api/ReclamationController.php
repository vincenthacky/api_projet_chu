<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reclamation;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Exception;

class ReclamationController extends Controller
{
    /**
     * Récupère toutes les réclamations avec pagination et recherche avancée.
     */
    public function index(Request $request)
    {
        try {
            $perPage   = $request->input('per_page', 15);
            $search    = $request->input('search');
            $statut    = $request->input('statut');
            $type      = $request->input('type');
            $priorite  = $request->input('priorite');
            $userName  = $request->input('utilisateur'); // nom ou prénom utilisateur
            $adminName = $request->input('admin');       // nom ou prénom admin

            $query = Reclamation::with(['souscription.utilisateur', 'statut']);

            // 🔎 Recherche globale
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_reclamation', 'like', "%{$search}%")
                    ->orWhere('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 🎯 Filtres spécifiques
            if ($statut) {
                $query->whereHas('statut', function ($q) use ($statut) {
                    $q->where('id_statut_reclamation', 'like', "%{$statut}%");
                });
            }

            if ($type) {
                $query->where('type_reclamation', $type);
            }

            if ($priorite) {
                $query->where('priorite', $priorite);
            }

            if ($userName) {
                $query->whereHas('souscription.utilisateur', function ($q) use ($userName) {
                    $q->where('nom', 'like', "%{$userName}%")
                    ->orWhere('prenom', 'like', "%{$userName}%");
                });
            }

            if ($adminName) {
                $query->whereHas('statut.admin', function ($q) use ($adminName) {
                    $q->where('nom', 'like', "%{$adminName}%")
                    ->orWhere('prenom', 'like', "%{$adminName}%");
                });
            }

            // 📌 Tri + pagination
            $reclamations = $query->orderBy('date_reclamation', 'desc')
                                ->paginate($perPage);

            return $this->responseSuccessPaginate($reclamations, "Liste des réclamations");

        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la récupération des réclamations : " . $e->getMessage(), 500);
        }
    }


     /**
     * Récupère toutes les réclamations avec pagination et recherche avancée.
     */
    public function indexUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
            $user = JWTAuth::parseToken()->authenticate();


            $query = Reclamation::with(['souscription', 'statut'])
             ->whereHas('souscription', function ($q) use ($user) {
                $q->where('id_utilisateur', $user->id_utilisateur);
            });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_reclamation', 'like', "%{$search}%")
                      ->orWhere('titre', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                     ;
                });
            }

            $reclamations = $query->orderBy('date_reclamation', 'desc')
                                  ->paginate($perPage);

            return $this->responseSuccessPaginate($reclamations, "Liste des réclamations");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des réclamations : " . $e->getMessage(), 500);
        }
    }


    /**
     * Récupérer une seule réclamation
     */
    public function show($id)
    {
        try {
            $reclamation = Reclamation::with(['souscription', 'statut'])->findOrFail($id);
            return $this->responseSuccess($reclamation, "Réclamation récupérée");
        } catch (Exception $e) {
            return $this->responseError("Réclamation introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer une nouvelle réclamation
     */
    public function store(Request $request, DocumentService $documentService)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_souscription'        => 'required|exists:Souscription,id_souscription',
                'titre'                  => 'required|string|max:255',
                'description'            => 'required|string',
                'type_reclamation'       => 'required|in:anomalie_paiement,information_erronee,document_manquant,avancement_projet,autre',
                'id_statut_reclamation'  => 'required|exists:StatutReclamation,id_statut_reclamation',
                'priorite'               => 'nullable|in:basse,normale,haute,urgente',
                'document'               => 'nullable|file|max:2048', // document facultatif
            ]);

            // Création de la réclamation
            $reclamation = Reclamation::create($request->only([
                'id_souscription',
                'titre',
                'description',
                'type_reclamation',
                'id_statut_reclamation',
                'priorite'
            ]));

            // Vérifier si un fichier est attaché → enregistrer via le DocumentService
            if ($request->hasFile('document')) {
                $documentService->store(
                    idSouscription: $request->id_souscription,
                    libelleTypeDocument: 'Réclamation - ' . $request->titre,
                    options: [
                        'source_table'         => 'reclamations',
                        'source_id'            => $reclamation->id_reclamation, // attention au nom de ta clé primaire
                        'description_document' => $request->description ?? 'Document lié à la réclamation',
                    ],
                    fichier: $request->file('document')
                );
            }

            DB::commit();

            return $this->responseSuccessMessage("Réclamation créée avec succès",  201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la création de la réclamation : " . $e->getMessage(), 500);
        }
    }
    /**
     * Mettre à jour une réclamation
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $reclamation = Reclamation::findOrFail($id);
            $reclamation->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Réclamation mise à jour avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la mise à jour de la réclamation : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer une réclamation
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $reclamation = Reclamation::findOrFail($id);
            $reclamation->delete();

            DB::commit();

            return $this->responseSuccessMessage("Réclamation supprimée avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la suppression de la réclamation : " . $e->getMessage(), 500);
        }
    }
}
