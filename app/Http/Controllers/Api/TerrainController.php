<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Terrain;
use Illuminate\Support\Facades\DB;
use Exception;

class TerrainController extends Controller
{
    /**
     * Récupère tous les terrains avec pagination et recherche avancée.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = Terrain::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_terrain', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%")
                      ->orWhere('localisation', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $terrains = $query->orderBy('date_creation', 'desc')
                              ->paginate($perPage);

            return $this->responseSuccessPaginate($terrains, "Liste des terrains");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des terrains : " . $e->getMessage(), 500);
        }
    }
    

     /**
     * Récupère la liste des terrains liés à l'utilisateur connecté
     */
    public function indexUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
            $user    = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();

            $query = Terrain::with('souscriptions')
                ->whereHas('souscriptions', function ($q) use ($user) {
                    $q->where('id_utilisateur', $user->id_utilisateur);
                });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_terrain', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%")
                      ->orWhere('localisation', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $terrains = $query->orderBy('date_creation', 'desc')
                              ->paginate($perPage);

            return $this->responseSuccessPaginate($terrains, "Liste des terrains de l'utilisateur");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des terrains : " . $e->getMessage(), 500);
        }
    }


    /**
     * Récupérer un terrain spécifique
     */
    public function show($id)
    {
        try {
            $terrain = Terrain::with('souscriptions')->findOrFail($id);
            return $this->responseSuccess($terrain, "Terrain récupéré");
        } catch (Exception $e) {
            return $this->responseError("Terrain introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un nouveau terrain
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'libelle'        => 'required|string|max:255',
                'localisation'   => 'required|string|max:255',
                'superficie'     => 'required|numeric|min:0',
                'prix_unitaire'  => 'required|numeric|min:0',
                'montant_mensuel'  => 'required|numeric|min:0',
                'description'    => 'nullable|string',
                'statut_terrain' => 'nullable|in:disponible,reserve,vendu,indisponible',
                'date_creation'  => 'nullable|date',
            ]);

            Terrain::create($request->only([
                'libelle',
                'localisation',
                'superficie',
                'prix_unitaire',
                'description',
                'montant_mensuel',
                'statut_terrain',
                'date_creation',
            ]));

            DB::commit();

            return $this->responseSuccessMessage("Terrain créé avec succès", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la création du terrain : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour un terrain
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $terrain = Terrain::findOrFail($id);

            $request->validate([
                'libelle'        => 'sometimes|required|string|max:255',
                'localisation'   => 'sometimes|required|string|max:255',
                'superficie'     => 'sometimes|required|numeric|min:0',
                'prix_unitaire'  => 'sometimes|required|numeric|min:0',
                'description'    => 'nullable|string',
                'statut_terrain' => 'nullable|in:disponible,reserve,vendu,indisponible',
                'coordonnees_gps'=> 'nullable|string|max:255',
                'date_creation'  => 'nullable|date',
            ]);

            $terrain->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Terrain mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la mise à jour du terrain : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un terrain
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $terrain = Terrain::findOrFail($id);
            $terrain->delete();

            DB::commit();

            return $this->responseSuccessMessage("Terrain supprimé avec succès");

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la suppression du terrain : " . $e->getMessage(), 500);
        }
    }
}
