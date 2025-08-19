<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reclamation;
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
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = Reclamation::with(['souscription', 'statut']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_reclamation', 'like', "%{$search}%")
                      ->orWhere('titre', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('souscription', function ($q2) use ($search) {
                          $q2->where('groupe_souscription', 'like', "%{$search}%");
                      });
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
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_souscription'     => 'required|exists:Souscription,id_souscription',
                'titre'               => 'required|string|max:255',
                'description'         => 'required|string',
                'type_reclamation'    => 'required|in:anomalie_paiement,information_erronee,document_manquant,avancement_projet,autre',
                'id_statut_reclamation'=> 'required|exists:StatutReclamation,id_statut_reclamation',
                'priorite'            => 'nullable|in:basse,normale,haute,urgente',
            ]);

            $reclamation = Reclamation::create($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Réclamation créée avec succès", 201);

        } catch (Exception $e) {
            DB::rollback();
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
