<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StatutReclamation;
use Illuminate\Support\Facades\DB;
use Exception;

class StatutReclamationController extends Controller
{
    /**
     * Récupère tous les statuts avec pagination et recherche
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = StatutReclamation::query();

            if ($search) {
                $query->where('libelle_statut_reclamation', 'like', "%{$search}%")
                      ->orWhere('description_statut', 'like', "%{$search}%");
            }

            $statuts = $query->orderBy('ordre_statut', 'asc')->paginate($perPage);

            return $this->responseSuccessPaginate($statuts, "Liste des statuts de réclamation");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des statuts : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un statut
     */
    public function show($id)
    {
        try {
            $statut = StatutReclamation::findOrFail($id);
            return $this->responseSuccess($statut, "Statut récupéré");
        } catch (Exception $e) {
            return $this->responseError("Statut introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un statut
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'libelle_statut_reclamation' => 'required|string|max:50',
                'description_statut'         => 'nullable|string',
                'ordre_statut'               => 'nullable|integer',
                'couleur_statut'             => 'nullable|string|size:7',
            ]);

            $statut = StatutReclamation::create($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Statut créé avec succès", 201);

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la création du statut : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour un statut
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $statut = StatutReclamation::findOrFail($id);
            $statut->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Statut mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la mise à jour du statut : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un statut
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $statut = StatutReclamation::findOrFail($id);
            $statut->delete();

            DB::commit();

            return $this->responseSuccessMessage("Statut supprimé avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la suppression du statut : " . $e->getMessage(), 500);
        }
    }
}
