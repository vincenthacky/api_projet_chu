<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Souscription;
use Illuminate\Support\Facades\DB;
use Exception;

class SouscriptionController extends Controller
{
    /**
     * Récupère toutes les souscriptions avec pagination et recherche avancée.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = Souscription::with(['utilisateur', 'terrain', 'admin']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_souscription', 'like', "%{$search}%")
                      ->orWhere('groupe_souscription', 'like', "%{$search}%")
                      ->orWhereHas('utilisateur', function ($q2) use ($search) {
                          $q2->where('nom', 'like', "%{$search}%")
                             ->orWhere('prenom', 'like', "%{$search}%")
                             ->orWhere('matricule', 'like', "%{$search}%");
                      })
                      ->orWhereHas('terrain', function ($q3) use ($search) {
                          $q3->where('libelle', 'like', "%{$search}%")
                             ->orWhere('localisation', 'like', "%{$search}%");
                      })
                      ->orWhereHas('admin', function ($q4) use ($search) {
                          $q4->where('nom', 'like', "%{$search}%")
                             ->orWhere('prenom', 'like', "%{$search}%");
                      });
                });
            }

            $souscriptions = $query->orderBy('date_souscription', 'desc')
                                   ->paginate($perPage);

            return $this->responseSuccessPaginate($souscriptions, "Liste des souscriptions");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des souscriptions : " . $e->getMessage(), 500);
        }
    }



    /**
     * Récupère toutes les souscriptions utilisateur connecter avec pagination et recherche avancée.
     */
    public function indexUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);
            $search  = $request->input('search');

            $query = Souscription::with(['terrain', 'admin', 'planpaiements'])
                ->where('id_utilisateur', 1);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_souscription', 'like', "%{$search}%")
                    ->orWhere('groupe_souscription', 'like', "%{$search}%")
                    ->orWhereHas('terrain', function ($q3) use ($search) {
                        $q3->where('libelle', 'like', "%{$search}%")
                            ->orWhere('localisation', 'like', "%{$search}%");
                    })
                    ->orWhereHas('admin', function ($q4) use ($search) {
                        $q4->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%");
                    });
                });
            }

            $souscriptions = $query->orderBy('created_at', 'asc')
                                ->paginate($perPage);

            // 🔥 On enrichit chaque souscription avec les données calculées
            $souscriptions->getCollection()->transform(function ($souscription) {
                $prixTotal = $souscription->terrain->prix_unitaire * $souscription->nombre_mensualites;
                $montantPaye = $souscription->montant_total_souscrit ?? 0;
                $reste = $prixTotal - $montantPaye;

                // dernier paiement
                $dernierPaiement = $souscription->planpaiements()
                                    ->orderBy('date_paiement_effectif', 'desc')
                                    ->first();

                $dateProchain = null;
                if ($dernierPaiement && $dernierPaiement->date_paiement_effectif) {
                    $dateProchain = \Carbon\Carbon::parse($dernierPaiement->date_paiement_effectif)
                                    ->addMonth()
                                    ->toDateString();
                }

                $souscription->prix_total_terrain = $prixTotal;
                $souscription->montant_paye = $montantPaye;
                $souscription->reste_a_payer = max($reste, 0);
                $souscription->date_prochain = $dateProchain;

                return $souscription;
            });

            return $this->responseSuccessPaginate($souscriptions, "Liste des souscriptions");

        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la récupération des souscriptions : " . $e->getMessage(), 500);
        }
    }





    /**
     * Récupérer une seule souscription
     */
    public function show($id)
    {
        try {
            $souscription = Souscription::with(['utilisateur', 'terrain', 'admin','planpaiements'])->findOrFail($id);
            return $this->responseSuccess($souscription, "Souscription récupérée");
        } catch (Exception $e) {
            return $this->responseError("Souscription introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer une nouvelle souscriptiondddepaul
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_utilisateur'     => 'required|exists:Utilisateur,id_utilisateur',
                'id_terrain'         => 'required|exists:Terrain,id_terrain',
                'id_admin'           => 'required|exists:Utilisateur,id_utilisateur',
                'nombre_terrains'    => 'required|integer|min:1',
                'montant_mensuel'    => 'required|numeric|min:0',
                'nombre_mensualites' => 'required|integer|min:1',
            ]);

            $souscription = Souscription::create($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Souscription créée avec succès", 201);

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la création de la souscription : " . $e->getMessage(), 500);
        }
    }

    /**
     * Mettre à jour une souscription
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $souscription = Souscription::findOrFail($id);
            $souscription->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage( "Souscription mise à jour avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la mise à jour de la souscription : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer une souscription
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $souscription = Souscription::findOrFail($id);
            $souscription->delete();

            DB::commit();

            return $this->responseSuccessMessage("Souscription supprimée avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la suppression de la souscription : " . $e->getMessage(), 500);
        }
    }
}
