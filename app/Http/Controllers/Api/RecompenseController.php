<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recompense;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Exception;

class RecompenseController extends Controller
{
    /**
     * Liste paginée des récompenses avec recherche avancée
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = Recompense::with(['souscription.utilisateur', 'typeRecompense']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_recompense', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('motif_recompense', 'like', "%{$search}%")
                      ->orWhere('periode_merite', 'like', "%{$search}%")
                      ->orWhereHas('typeRecompense', function ($q2) use ($search) {
                          $q2->where('libelle_type_recompense', 'like', "%{$search}%");
                      })
                     ;
                });
            }

            $recompenses = $query->orderBy('date_attribution', 'desc')
                                 ->paginate($perPage);

            return $this->responseSuccessPaginate($recompenses, "Liste des récompenses");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des récompenses : " . $e->getMessage(), 500);
        }
    }

    /**
     * Liste paginée des récompenses avec recherche avancée
     */
    public function indexUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
             $user = JWTAuth::parseToken()->authenticate();

            $query = Recompense::with(['souscription.utilisateur', 'typeRecompense'])
            ->whereHas('souscription', function ($q) use ($user) {
                $q->where('id_utilisateur', $user->id_utilisateur);
            });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_recompense', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('motif_recompense', 'like', "%{$search}%")
                      ->orWhere('periode_merite', 'like', "%{$search}%")
                      ->orWhereHas('typeRecompense', function ($q2) use ($search) {
                          $q2->where('libelle_type_recompense', 'like', "%{$search}%");
                      })
                     ;
                });
            }

            $recompenses = $query->orderBy('date_attribution', 'desc')
                                 ->paginate($perPage);

            return $this->responseSuccessPaginate($recompenses, "Liste des récompenses");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des récompenses : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer une récompense par son ID
     */
    public function show($id)
    {
        try {
            $recompense = Recompense::with(['souscription.utilisateur', 'typeRecompense'])->findOrFail($id);
            return $this->responseSuccess($recompense, "Récompense récupérée");
        } catch (Exception $e) {
            return $this->responseError("Récompense introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer une récompense
     */
    public function store(Request $request, DocumentService $documentService)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_souscription'     => 'required|exists:Souscription,id_souscription',
                'id_type_recompense'  => 'required|exists:TypeRecompense,id_type_recompense',
                'description'         => 'required|string',
                'motif_recompense'    => 'required|string',
                'periode_merite'      => 'nullable|string|max:100',
                'valeur_recompense'   => 'nullable|numeric|min:0',
                'statut_recompense'   => 'nullable|in:due,attribuee,annulee',
                'date_attribution'    => 'nullable|date',
                'date_attribution_effective' => 'nullable|date',
                'commentaire_admin'   => 'nullable|string',
                'document'            => 'nullable|file|max:4096', // fichier facultatif
            ]);

            // Création de la récompense
            $recompense = Recompense::create($request->only([
                'id_souscription',
                'id_type_recompense',
                'description',
                'motif_recompense',
                'periode_merite',
                //'valeur_recompense',
                'statut_recompense',
                'date_attribution',
                'date_attribution_effective',
                'commentaire_admin',
            ]));

            // Si un document est attaché → stocker via DocumentService
            if ($request->hasFile('document')) {
                $documentService->store(
                    idSouscription: $request->id_souscription,
                    libelleTypeDocument: 'Récompense - ' . $recompense->typeRecompense->libelle_type_recompense,
                    options: [
                        'source_table'         => 'Recompense',
                        'source_id'            => $recompense->id_recompense,
                        'description_document' => $request->description ?? 'Document lié à la récompense',
                    ],
                    fichier: $request->file('document')
                );
            }

            DB::commit();

            return $this->responseSuccessMessage("Récompense créée avec succès", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la création de la récompense : " . $e->getMessage(), 500);
        }
    }
    

    /**
     * Mettre à jour une récompense
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $recompense = Recompense::findOrFail($id);

            $request->validate([
                'id_type_recompense'  => 'sometimes|exists:TypeRecompense,id_type_recompense',
                'description'         => 'sometimes|string',
                'motif_recompense'    => 'sometimes|string',
                'periode_merite'      => 'nullable|string|max:100',
                'valeur_recompense'   => 'nullable|numeric|min:0',
                'statut_recompense'   => 'nullable|in:due,attribuee,annulee',
                'date_attribution'    => 'nullable|date',
                'date_attribution_effective' => 'nullable|date',
                'commentaire_admin'   => 'nullable|string',
            ]);

            $recompense->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Récompense mise à jour avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la mise à jour de la récompense : " . $e->getMessage(), 500);
        }
    }

    /**
     * Modifier uniquement le statut d'une récompense
     */
    public function updateStatut(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Validation des données
            $request->validate([
                'statut_recompense' => 'required|in:due,attribuee,annulee',
            ]);

            // Récupération de la récompense
            $recompense = Recompense::findOrFail($id);

            // Mise à jour du statut
            $recompense->update([
                'statut_recompense' => $request->statut_recompense,
            ]);

            DB::commit();

            return $this->responseSuccessMessage("Statut de la récompense mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la mise à jour du statut : " . $e->getMessage(), 500);
        }
    }


    /**
     * Supprimer une récompense
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $recompense = Recompense::findOrFail($id);
            $recompense->delete();

            DB::commit();

            return $this->responseSuccessMessage("Récompense supprimée avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la suppression de la récompense : " . $e->getMessage(), 500);
        }
    }
}
