<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlanPaiement;
use App\Models\Souscription;
use Illuminate\Support\Facades\DB;
use Exception;

class PlanPaiementController extends Controller
{
    /**
     * Récupère tous les paiements avec pagination et recherche avancée.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = PlanPaiement::with(['souscription']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_plan_paiement', 'like', "%{$search}%")
                      ->orWhere('numero_mensualite', 'like', "%{$search}%")
                      ->orWhereHas('souscription', function ($q2) use ($search) {
                          $q2->where('groupe_souscription', 'like', "%{$search}%");
                      });
                });
            }

            $paiements = $query->orderBy('date_limite_versement', 'desc')
                               ->paginate($perPage);


            // Calcul des statistiques
            $totals = [
                'total_mensualites' => PlanPaiement::count(),
                'total_paye_a_temps' => PlanPaiement::where('statut_versement', PlanPaiement::STATUT_PAYE_A_TEMPS)->count(),
                'total_en_retard' => PlanPaiement::where('statut_versement', PlanPaiement::STATUT_PAYE_EN_RETARD)->count(),
                'total_en_attente' => PlanPaiement::where('statut_versement', PlanPaiement::STATUT_EN_ATTENTE)->count(),
            ];

            return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => "Liste des paiements récupérée avec succès",
            'data' => $paiements->items(),
            'pagination' => [
                'total' => $paiements->total(),
                'per_page' => $paiements->perPage(),
                'current_page' => $paiements->currentPage(),
                'last_page' => $paiements->lastPage(),
                'from' => $paiements->firstItem(),
                'to' => $paiements->lastItem(),
            ],
            'statistiques' => $totals,
            ]);


        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des paiements : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un seul paiement
     */
    public function show($id)
    {
        try {
            $paiement = PlanPaiement::with(['souscription'])->findOrFail($id);
            return $this->responseSuccess($paiement, "Paiement récupéré");
        } catch (Exception $e) {
            return $this->responseError("Paiement introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un nouveau paiement
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_souscription' => 'required|exists:Souscription,id_souscription',
                'mode_paiement'   => 'nullable|in:especes,virement,mobile_money,cheque',
                'montant_paye'    => 'nullable|numeric|min:0',
                'date_paiement_effectif' => 'nullable|date',
            ]);

            // Récupérer la souscription
            $souscription = Souscription::findOrFail($request->id_souscription);

            // Déterminer le prochain numéro de mensualité
            $dernierPaiement = $souscription->planPaiements()->orderBy('numero_mensualite', 'desc')->first();
            $numeroMensualite = $dernierPaiement ? $dernierPaiement->numero_mensualite + 1 : 1;

            // Vérifier qu'on ne dépasse pas le nombre total de mensualités
            if ($numeroMensualite > $souscription->nombre_mensualites) {
                return $this->responseError("Toutes les mensualités de cette souscription ont déjà été créées.", 400);
            }

            // Montant du versement prévu
            $montantVersementPrevu = $souscription->montant_mensuel;

            // Calcul de la date limite : date_debut_paiement + (numeroMensualite - 1) mois
            $dateLimiteVersement = \Carbon\Carbon::parse($souscription->date_debut_paiement)
                                    ->addMonths($numeroMensualite - 1)
                                    ->format('Y-m-d');

            // Créer le paiement
            $paiement = PlanPaiement::create([
                'id_souscription'         => $souscription->id_souscription,
                'numero_mensualite'       => $numeroMensualite,
                'montant_versement_prevu' => $montantVersementPrevu,
                'date_limite_versement'   => $dateLimiteVersement,
                'montant_paye'            => $request->input('montant_paye', null),
                'date_paiement_effectif'  => $request->input('date_paiement_effectif', null),
                'mode_paiement'           => $request->input('mode_paiement', null),
            ]);

            DB::commit();

            return $this->responseSuccessMessage("Paiement créé avec succès", 201);

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la création du paiement : " . $e->getMessage(), 500);
        }
    }


    /**
     * Mettre à jour un paiement
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $paiement = PlanPaiement::findOrFail($id);
            $paiement->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Paiement mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la mise à jour du paiement : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un paiement
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $paiement = PlanPaiement::findOrFail($id);
            $paiement->delete();

            DB::commit();

            return $this->responseSuccessMessage("Paiement supprimé avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la suppression du paiement : " . $e->getMessage(), 500);
        }
    }
}
