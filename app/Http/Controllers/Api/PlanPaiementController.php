<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlanPaiement;
use Tymon\JWTAuth\Facades\JWTAuth;
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

            $query = PlanPaiement::with(['souscription.utilisateur']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_plan_paiement', 'like', "%{$search}%")
                      ->orWhere('numero_mensualite', 'like', "%{$search}%")
                      ;
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
     * Récupère tous les paiements avec pagination et recherche avancée.
     */
    public function indexUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
            $user = JWTAuth::parseToken()->authenticate();

          $query = PlanPaiement::with(['souscription'])
        ->whereHas('souscription', function ($q) use ($user) {
            $q->where('id_utilisateur', $user->id_utilisateur);
        });


            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_plan_paiement', 'like', "%{$search}%")
                      ->orWhere('numero_mensualite', 'like', "%{$search}%")
                     ;
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
    // public function store(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $request->validate([
    //             'id_souscription' => 'required|exists:Souscription,id_souscription',
    //             'mode_paiement'   => 'nullable|in:especes,virement,mobile_money,cheque',
    //             'montant_paye'    => 'nullable|numeric|min:0',
    //             'date_paiement_effectif' => 'nullable|date',
    //         ]);

    //         // Récupérer la souscription
    //         $souscription = Souscription::findOrFail($request->id_souscription);

    //         // Déterminer le prochain numéro de mensualité
    //         $dernierPaiement = $souscription->planPaiements()->orderBy('numero_mensualite', 'desc')->first();
    //         $numeroMensualite = $dernierPaiement ? $dernierPaiement->numero_mensualite + 1 : 1;

    //         // Vérifier qu'on ne dépasse pas le nombre total de mensualités
    //         if ($numeroMensualite > $souscription->nombre_mensualites) {
    //             return $this->responseError("Toutes les mensualités de cette souscription ont déjà été créées.", 400);
    //         }

    //         // Montant du versement prévu
    //         $montantVersementPrevu = $souscription->montant_mensuel;

    //         // Calcul de la date limite : date_debut_paiement + (numeroMensualite - 1) mois
    //         $dateLimiteVersement = \Carbon\Carbon::parse($souscription->date_debut_paiement)
    //                                 ->addMonths($numeroMensualite - 1)
    //                                 ->format('Y-m-d');

    //         // Créer le paiement
    //         $paiement = PlanPaiement::create([
    //             'id_souscription'         => $souscription->id_souscription,
    //             'numero_mensualite'       => $numeroMensualite,
    //             'montant_versement_prevu' => $montantVersementPrevu,
    //             'date_limite_versement'   => $dateLimiteVersement,
    //             'montant_paye'            => $request->input('montant_paye', null),
    //             'date_paiement_effectif'  => $request->input('date_paiement_effectif', null),
    //             'mode_paiement'           => $request->input('mode_paiement', null),
    //         ]);

    //         DB::commit();

    //         return $this->responseSuccessMessage("Paiement créé avec succès", 201);

    //     } catch (Exception $e) {
    //         DB::rollback();
    //         return $this->responseError("Erreur lors de la création du paiement : " . $e->getMessage(), 500);
    //     }
    // }

    /**
     * Créer un nouveau paiement avec calculs et statut intelligent
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_souscription' => 'required|exists:Souscription,id_souscription',
                'mode_paiement'   => 'nullable|in:' . implode(',', [
                    PlanPaiement::MODE_ESPECES,
                    PlanPaiement::MODE_VIREMENT,
                    PlanPaiement::MODE_MOBILE_MONEY,
                    PlanPaiement::MODE_CHEQUE
                ]),
                'montant_paye'    => 'nullable|numeric|min:0',
                'date_paiement_effectif' => 'nullable|date',
                'commentaire_paiement' => 'nullable|string|max:255',
                'reference_paiement' => 'nullable|string|max:100',
            ]);

            // Récupérer la souscription
            $souscription = Souscription::findOrFail($request->id_souscription);

            // Déterminer le prochain numéro de mensualité
            $dernierPaiement = $souscription->planPaiements()->orderBy('numero_mensualite', 'desc')->first();
            $numeroMensualite = $dernierPaiement ? $dernierPaiement->numero_mensualite + 1 : 1;

            if ($numeroMensualite > $souscription->nombre_mensualites) {
                return $this->responseError("Toutes les mensualités de cette souscription ont déjà été créées.", 400);
            }

            // Montant et date limite du versement
            $montantVersementPrevu = $souscription->montant_mensuel;
            $dateLimiteVersement = \Carbon\Carbon::parse($souscription->date_debut_paiement)
                                        ->addMonths($numeroMensualite - 1)
                                        ->format('Y-m-d');

            // Déterminer le montant payé
            $montantPaye = $request->input('montant_paye', 0);

            // Calcul de pénalité si paiement après la date limite
            $penalite = 0;
            $datePaiementEffectif = $request->input('date_paiement_effectif') 
                                    ? \Carbon\Carbon::parse($request->date_paiement_effectif) 
                                    : null;
            if ($datePaiementEffectif && $datePaiementEffectif->gt($dateLimiteVersement)) {
                // Exemple : pénalité 2% du montant par mois de retard
                $moisRetard = $datePaiementEffectif->diffInMonths($dateLimiteVersement);
                $penalite = $montantVersementPrevu * 0.02 * $moisRetard;
            }

            // Déterminer le statut
            if ($montantPaye == 0) {
                $statut = PlanPaiement::STATUT_NON_PAYE;
                $estPaye = false;
            } elseif ($datePaiementEffectif && $datePaiementEffectif->lte($dateLimiteVersement)) {
                $statut = PlanPaiement::STATUT_PAYE_A_TEMPS;
                $estPaye = true;
            } else {
                $statut = PlanPaiement::STATUT_PAYE_EN_RETARD;
                $estPaye = true;
            }

            // Générer une référence unique
           // $referencePaiement = 'PAY-' . strtoupper(uniqid());

            // Créer le paiement
            $paiement = PlanPaiement::create([
                'id_souscription'         => $souscription->id_souscription,
                'numero_mensualite'       => $numeroMensualite,
                'montant_versement_prevu' => $montantVersementPrevu,
                'date_limite_versement'   => $dateLimiteVersement,
                'montant_paye'            => $montantPaye,
                'date_paiement_effectif'  => $datePaiementEffectif,
                'mode_paiement'           => $request->input('mode_paiement', null),
                'reference_paiement'      => $request->input('reference_paiement', null),
                'penalite_appliquee'      => $penalite,
                'est_paye'                => $estPaye,
                'statut_versement'        => $statut,
                'commentaire_paiement'    => $request->input('commentaire_paiement', null),
                'date_saisie'             => now(),
            ]);

            DB::commit();
            return $this->responseSuccessMessage("Paiement créé avec succès", 201);

        } catch (\Exception $e) {
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
