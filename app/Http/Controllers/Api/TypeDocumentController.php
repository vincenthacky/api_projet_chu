<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TypeDocument;
use Illuminate\Support\Facades\DB;
use Exception;

class TypeDocumentController extends Controller
{
    /**
     * Récupérer tous les types de documents avec pagination et recherche.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = TypeDocument::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('libelle_type_document', 'like', "%{$search}%")
                      ->orWhere('description_type', 'like', "%{$search}%")
                      ->orWhere('extension_autorisee', 'like', "%{$search}%");
                });
            }

            $types = $query->orderBy('libelle_type_document', 'asc')
                           ->paginate($perPage);

            return $this->responseSuccessPaginate($types, "Liste des types de documents");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des types de documents : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un type de document par ID
     */
    public function show($id)
    {
        try {
            $type = TypeDocument::findOrFail($id);

            return $this->responseSuccess($type, "Type de document récupéré");
        } catch (Exception $e) {
            return $this->responseError("Type de document introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un nouveau type de document
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'libelle_type_document' => 'required|string|max:100',
                'description_type'      => 'nullable|string',
                'extension_autorisee'   => 'nullable|string|max:50',
                'taille_max_mo'         => 'nullable|integer|min:1',
                'est_obligatoire'       => 'nullable|boolean',
            ]);

            $type = TypeDocument::create($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Type de document créé avec succès", 201);

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la création du type de document : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour un type de document
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $type = TypeDocument::findOrFail($id);

            $request->validate([
                'libelle_type_document' => 'sometimes|required|string|max:100',
                'description_type'      => 'nullable|string',
                'extension_autorisee'   => 'nullable|string|max:50',
                'taille_max_mo'         => 'nullable|integer|min:1',
                'est_obligatoire'       => 'nullable|boolean',
            ]);

            $type->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Type de document mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la mise à jour du type de document : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un type de document
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $type = TypeDocument::findOrFail($id);
            $type->delete();

            DB::commit();

            return $this->responseSuccessMessage("Type de document supprimé avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la suppression du type de document : " . $e->getMessage(), 500);
        }
    }
}
