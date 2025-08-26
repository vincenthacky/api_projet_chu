<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeRecompense;
use Illuminate\Support\Facades\DB;
use Exception;

class TypeRecompenseController extends Controller
{
    /**
     * Liste paginée des types de récompenses avec recherche
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = TypeRecompense::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_type_recompense', 'like', "%{$search}%")
                      ->orWhere('libelle_type_recompense', 'like', "%{$search}%")
                      ->orWhere('description_type', 'like', "%{$search}%")
                      ->orWhere('conditions_attribution', 'like', "%{$search}%");
                });
            }

            $types = $query->orderBy('libelle_type_recompense', 'asc')
                           ->paginate($perPage);

            return $this->responseSuccessPaginate($types, "Liste des types de récompenses");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des types de récompense : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un type de récompense par ID
     */
    public function show($id)
    {
        try {
            $type = TypeRecompense::findOrFail($id);
            return $this->responseSuccess($type, "Type de récompense récupéré");
        } catch (Exception $e) {
            return $this->responseError("Type de récompense introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un type de récompense
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'libelle_type_recompense' => 'required|string|max:100',
                'description_type'        => 'nullable|string',
                'valeur_monetaire'        => 'nullable|numeric|min:0',
                'est_monetaire'           => 'boolean',
                'conditions_attribution'  => 'nullable|string',
            ]);

            $type = TypeRecompense::create($request->only([
                'libelle_type_recompense',
                'description_type',
                'valeur_monetaire',
                'est_monetaire',
                'conditions_attribution',
            ]));

            DB::commit();

            return $this->responseSuccessMessage("Type de récompense créé avec succès", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la création du type de récompense : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour un type de récompense
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $type = TypeRecompense::findOrFail($id);

            $request->validate([
                'libelle_type_recompense' => 'sometimes|string|max:100',
                'description_type'        => 'nullable|string',
                'valeur_monetaire'        => 'nullable|numeric|min:0',
                'est_monetaire'           => 'boolean',
                'conditions_attribution'  => 'nullable|string',
            ]);

            $type->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Type de récompense mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la mise à jour du type de récompense : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un type de récompense
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $type = TypeRecompense::findOrFail($id);
            $type->delete();

            DB::commit();

            return $this->responseSuccessMessage("Type de récompense supprimé avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la suppression du type de récompense : " . $e->getMessage(), 500);
        }
    }
}
