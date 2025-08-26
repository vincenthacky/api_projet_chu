<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeEvenement;
use Exception;

class TypeEvenementController extends Controller
{
    /**
     * Liste paginée des types d'événement avec recherche.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = TypeEvenement::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('libelle_type_evenement', 'like', "%{$search}%")
                      ->orWhere('description_type', 'like', "%{$search}%")
                      ->orWhere('categorie_type', 'like', "%{$search}%");
                });
            }

            $types = $query->orderBy('ordre_affichage')
                           ->paginate($perPage);

            return $this->responseSuccessPaginate($types, "Liste des types d'événement");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des types d'événement : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un type d'événement par son ID
     */
    public function show($id)
    {
        try {
            $type = TypeEvenement::findOrFail($id);
            return $this->responseSuccess($type, "Type d'événement récupéré");
        } catch (Exception $e) {
            return $this->responseError("Type d'événement introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un nouveau type d'événement
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'libelle_type_evenement' => 'required|string|max:100',
                'description_type'       => 'nullable|string',
                'categorie_type'         => 'required|in:travaux_terrain,administrative,communication,livraison,maintenance',
                'couleur_affichage'      => 'nullable|string|max:7',
                'icone_type'             => 'nullable|string|max:50',
                'ordre_affichage'        => 'nullable|integer',
            ]);

            $type = TypeEvenement::create($request->all());

            return $this->responseSuccessMessage("Type d'événement créé avec succès", 201);

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la création du type d'événement : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour un type d'événement
     */
    public function update(Request $request, $id)
    {
        try {
            $type = TypeEvenement::findOrFail($id);

            $request->validate([
                'libelle_type_evenement' => 'sometimes|string|max:100',
                'description_type'       => 'nullable|string',
                'categorie_type'         => 'sometimes|in:travaux_terrain,administrative,communication,livraison,maintenance',
                'couleur_affichage'      => 'nullable|string|max:7',
                'icone_type'             => 'nullable|string|max:50',
                'ordre_affichage'        => 'nullable|integer',
            ]);

            $type->update($request->all());

            return $this->responseSuccessMessage("Type d'événement mis à jour avec succès");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la mise à jour du type d'événement : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un type d'événement
     */
    public function destroy($id)
    {
        try {
            $type = TypeEvenement::findOrFail($id);
            $type->delete();

            return $this->responseSuccessMessage("Type d'événement supprimé avec succès");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la suppression du type d'événement : " . $e->getMessage(), 500);
        }
    }
}
