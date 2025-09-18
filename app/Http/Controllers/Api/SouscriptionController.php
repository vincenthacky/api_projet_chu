<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Souscription;
use App\Models\Terrain;
use App\Models\Utilisateur;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class SouscriptionController extends Controller
{
    /**
     * Récupère toutes les souscriptions avec pagination et recherche avancée.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);
            $search  = $request->input('search');
        
            $query = Souscription::with(['terrain', 'admin', 'utilisateur', 'planpaiements'])
                ->where('statut_souscription', '=', Souscription::STATUT_ACTIVE);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_souscription', 'like', "%{$search}%")
                   
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

            // 🔥 Enrichir chaque souscription
            $souscriptions->getCollection()->transform(function ($souscription) {

                // Prix total du terrain
                $prixTotal = $souscription->terrain->montant_mensuel * $souscription->nombre_mensualites;

                // Montant payé
                $montantPaye = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->sum('montant_paye');

                // Reste à payer
                $reste = $prixTotal - $montantPaye;

                // ✅ Détermination du statut dynamique
                if ($montantPaye == 0) {
                    $statut = Souscription::STATUT_EN_ATTENTE;
                } elseif ($reste <= 0) {
                    $statut = Souscription::STATUT_TERMINEE;
                } else {
                    $statut = Souscription::STATUT_EN_COUR;
                }

                // ✅ Détermination de la date du prochain paiement
                $dernierPaiement = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->orderBy('date_paiement_effectif', 'desc')
                                    ->first();

                if ($dernierPaiement) {
                    $dateProchain = Carbon::parse($dernierPaiement->date_paiement_effectif)
                                        ->addMonthNoOverflow()
                                        ->format('Y-m-d');
                } else {
                    $dateProchain = Carbon::parse($souscription->date_debut_paiement ?? $souscription->date_souscription)
                                        ->addMonthNoOverflow()
                                        ->format('Y-m-d');
                }

                // Injecter dans l’objet retourné
                $souscription->prix_total_terrain = $prixTotal;
                $souscription->montant_paye = $montantPaye;
                $souscription->reste_a_payer = max($reste, 0);
                $souscription->date_prochain = $dateProchain;
                $souscription->statut_dynamique = $statut;

                return $souscription;
            });

            return $this->responseSuccessPaginate($souscriptions, "Liste des souscriptions");

        } catch (\Exception $e) {
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
            $user = JWTAuth::parseToken()->authenticate();

            $query = Souscription::with(['terrain', 'admin', 'planpaiements'])
                ->where('id_utilisateur', $user->id_utilisateur)->where('statut_souscription', '=', Souscription::STATUT_ACTIVE);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_souscription', 'like', "%{$search}%")
                  
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

            // 🔥 Enrichir chaque souscription
            $souscriptions->getCollection()->transform(function ($souscription) {
                // Prix total du terrain
                $prixTotal = $souscription->terrain->montant_mensuel * $souscription->nombre_mensualites;

                // Montant payé = somme des paiements effectués
                $montantPaye = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->sum('montant_paye');

                // Reste à payer
                $reste = $prixTotal - $montantPaye;

                // 🔥 Détermination du statut dynamique
                if ($montantPaye == 0) {
                    $statut = Souscription::STATUT_EN_ATTENTE;
                } elseif ($reste <= 0) {
                    $statut = Souscription::STATUT_TERMINEE;
                } else {
                    $statut = Souscription::STATUT_EN_COUR; // statut calculé, non présent dans la table
                }

                // 🔥 Détermination de la date du prochain paiement
                $dernierPaiement = $souscription->planpaiements()
                                        ->whereNotNull('date_paiement_effectif')
                                        ->orderBy('date_paiement_effectif', 'desc')
                                        ->first();

                if ($dernierPaiement) {
                    $dateProchain = Carbon::parse($dernierPaiement->date_paiement_effectif)->addMonth()->format('Y-m-d');
                } else {
                    $dateProchain = Carbon::parse($souscription->date_debut_paiement ?? $souscription->date_souscription)
                                        ->addMonth()
                                        ->format('Y-m-d');
                }

                // Injecter dans l’objet retourné
                $souscription->prix_total_terrain = $prixTotal;
                $souscription->montant_paye = $montantPaye;
                $souscription->reste_a_payer = max($reste, 0);
                $souscription->date_prochain = $dateProchain;
                $souscription->statut_dynamique = $statut; // ⚡ Nouveau champ calculé

                return $souscription;
            });


            return $this->responseSuccessPaginate($souscriptions, "Liste des souscriptions");

        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la récupération des souscriptions : " . $e->getMessage(), 500);
        }
    }



    
    /**
     * Récupérer toutes les demandes de souscription utilisateur en attente.
     */
    public function indexDemandes(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search  = $request->input('search');

            $query = Souscription::with(['utilisateur', 'terrain','admin'])
                ->where('origine', Souscription::ORIGINE_UTILISATEUR)
                ->where('statut_souscription', Souscription::STATUT_EN_ATTENTE);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('utilisateur', function ($q2) use ($search) {
                        $q2->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%");
                    })
                    ->orWhereHas('terrain', function ($q3) use ($search) {
                        $q3->where('libelle', 'like', "%{$search}%")
                        ->orWhere('localisation', 'like', "%{$search}%");
                    });
                });
            }

            $demandes = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            // 🔥 Enrichir chaque demande
           // 🔥 Enrichir chaque demande
            $demandes->getCollection()->transform(function ($souscription) {

                // Prix total du terrain
                $prixTotal = $souscription->terrain->montant_mensuel * $souscription->nombre_mensualites;

                // Montant payé
                $montantPaye = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->sum('montant_paye');

                // Reste à payer
                $reste = $prixTotal - $montantPaye;

                // ✅ Détermination du statut dynamique
                if ($montantPaye == 0) {
                    $statut = Souscription::STATUT_EN_ATTENTE;
                } elseif ($reste <= 0) {
                    $statut = Souscription::STATUT_TERMINEE;
                } else {
                    $statut = Souscription::STATUT_EN_COUR;
                }

                // ✅ Détermination de la date du prochain paiement
                $dernierPaiement = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->orderBy('date_paiement_effectif', 'desc')
                                    ->first();

                if ($dernierPaiement) {
                    $dateProchain = Carbon::parse($dernierPaiement->date_paiement_effectif)
                                        ->addMonthNoOverflow()
                                        ->format('Y-m-d');
                } else {
                    $dateProchain = Carbon::parse($souscription->date_debut_paiement ?? $souscription->date_souscription)
                                        ->addMonthNoOverflow()
                                        ->format('Y-m-d');
                }

                // Injecter dans l’objet retourné
                $souscription->prix_total_terrain = $prixTotal;
                $souscription->montant_paye = $montantPaye;
                $souscription->reste_a_payer = max($reste, 0);
                $souscription->date_prochain = $dateProchain;
                $souscription->statut_dynamique = $statut;

                return $souscription;
            });

            return $this->responseSuccessPaginate($demandes, "Liste des demandes de souscription");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des demandes : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer toutes les demandes de souscription de l'utilisateur connecté en attente.
     */
    public function indexDemandesUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search  = $request->input('search');

            $user = JWTAuth::parseToken()->authenticate();

            $query = Souscription::with(['utilisateur', 'terrain', 'admin', 'planpaiements'])
                ->where('origine', Souscription::ORIGINE_UTILISATEUR)
                ->where('statut_souscription', Souscription::STATUT_EN_ATTENTE)
                ->where('id_utilisateur', $user->id_utilisateur);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('terrain', function ($q2) use ($search) {
                        $q2->where('libelle', 'like', "%{$search}%")
                        ->orWhere('localisation', 'like', "%{$search}%");
                    });
                });
            }

            $demandes = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            // 🔥 Enrichir chaque demande
            $demandes->getCollection()->transform(function ($souscription) {

                // Prix total du terrain
                $prixTotal = $souscription->terrain->montant_mensuel * $souscription->nombre_mensualites;

                // Montant payé
                $montantPaye = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->sum('montant_paye');

                // Reste à payer
                $reste = $prixTotal - $montantPaye;

                // ✅ Détermination du statut dynamique
                if ($montantPaye == 0) {
                    $statut = Souscription::STATUT_EN_ATTENTE;
                } elseif ($reste <= 0) {
                    $statut = Souscription::STATUT_TERMINEE;
                } else {
                    $statut = Souscription::STATUT_EN_COUR;
                }

                // ✅ Détermination de la date du prochain paiement
                $dernierPaiement = $souscription->planpaiements()
                                    ->whereNotNull('date_paiement_effectif')
                                    ->orderBy('date_paiement_effectif', 'desc')
                                    ->first();

                if ($dernierPaiement) {
                    $dateProchain = Carbon::parse($dernierPaiement->date_paiement_effectif)
                                        ->addMonthNoOverflow()
                                        ->format('Y-m-d');
                } else {
                    $dateProchain = Carbon::parse($souscription->date_debut_paiement ?? $souscription->date_souscription)
                                        ->addMonthNoOverflow()
                                        ->format('Y-m-d');
                }

                // Injecter dans l’objet retourné
                $souscription->prix_total_terrain = $prixTotal;
                $souscription->montant_paye = $montantPaye;
                $souscription->reste_a_payer = max($reste, 0);
                $souscription->date_prochain = $dateProchain;
                $souscription->statut_dynamique = $statut;

                return $souscription;
            });

            return $this->responseSuccessPaginate($demandes, "Liste des demandes de souscription de l'utilisateur");

        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de la récupération des demandes : " . $e->getMessage(), 500);
        }
    }



    /**
     * Créer une demande de souscription utilisateur.
     */
    public function storeDemande(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = JWTAuth::parseToken()->authenticate(); // utilisateur connecté
            $request->validate([
                'id_terrain'      => 'required|exists:Terrain,id_terrain',
                'nombre_terrains' => 'sometimes|integer|min:1',
                'montant_mensuel' => 'sometimes|numeric|min:0',
                'nombre_mensualites' => 'sometimes|integer|min:1',
            ]);

            $terrain = Terrain::find($request->id_terrain);
            if (!$terrain ) {
            return $this->responseError("Le terrain non trouver  pour créer une souscription.", 403);   
            }


            $demande = Souscription::create([
                'id_utilisateur' => $user->id_utilisateur,
                'montant_mensuel'     => $terrain->montant_mensuel,
                'id_terrain'     => $request->id_terrain,
                'origine'        => Souscription::ORIGINE_UTILISATEUR,
                'statut_souscription' => Souscription::STATUT_EN_ATTENTE,
            ]);

            DB::commit();
            return $this->responseSuccessMessage("Demande de souscription créée avec succès", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseError("Erreur lors de la création de la demande : " . $e->getMessage(), 500);
        }
    }

    /**
     * Changer le statut d'une demande de souscription (valider ou rejeter)
     */
    public function changerStatutDemande(Request $request, $id)
    {
        $request->validate([
            'statut_souscription' => 'required|in:' . implode(',', [
                Souscription::STATUT_ACTIVE,
                Souscription::STATUT_REJETE
            ]),
        ]);

        try {
            $user = JWTAuth::parseToken()->authenticate();

            // Vérification du rôle de l’utilisateur (à adapter selon ton modèle Utilisateur)
            if (!in_array($user->type, [Souscription::ORIGINE_ADMIN, Souscription::ORIGINE_SUPER_ADMIN])) {
                return $this->responseError(
                    "Accès refusé. Seuls les administrateurs peuvent changer le statut des demandes.", 
                    403
                );
            }

            $demande = Souscription::where('origine', Souscription::ORIGINE_UTILISATEUR)
                ->where('id_souscription', $id)
                ->firstOrFail();

            // Vérifier si le statut est déjà le même
            if ($demande->statut_souscription === $request->statut_souscription) {
                return $this->responseSuccessMessage("Le statut est déjà défini sur {$request->statut_souscription}");
            }

            $demande->update([
                'statut_souscription' => $request->statut_souscription,
                'id_admin'            => $user->id_utilisateur,
            ]);

            return $this->responseSuccessMessage("Statut de la demande mis à jour avec succès");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la mise à jour du statut : " . $e->getMessage(), 500);
        }
    }







    /**
     * Récupérer une seule souscription
     */
    public function show($id)
    {
        try {
            $souscription = Souscription::with(['utilisateur', 'terrain', 'admin', 'planpaiements'])
                ->findOrFail($id);

            // Prix total du terrain
            $prixTotal = $souscription->terrain->prix_unitaire * $souscription->nombre_mensualites;

            // Montant réellement payé dans PlanPaiement
            $montantPaye = $souscription->planpaiements()
                                ->whereNotNull('date_paiement_effectif')
                                ->sum('montant_paye');

            // Reste à payer
            $reste = $prixTotal - $montantPaye;

            // Prochain paiement prévu
            $prochainPaiement = $souscription->planpaiements()
                                    ->whereNull('date_paiement_effectif')
                                    ->orderBy('date_limite_versement', 'asc')
                                    ->first();

            $dateProchain = $prochainPaiement ? $prochainPaiement->date_limite_versement : null;

            // Injection des champs calculés
            $souscription->prix_total_terrain = $prixTotal;
            $souscription->montant_paye = $montantPaye;
            $souscription->reste_a_payer = max($reste, 0);
            $souscription->date_prochain = $dateProchain;

            return $this->responseSuccess($souscription, "Souscription récupérée");

        } catch (\Exception $e) {
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
            $user = JWTAuth::parseToken()->authenticate(); // admin connecté
            $request->validate([
                'id_utilisateur'     => 'required|exists:Utilisateur,id_utilisateur',
                'id_terrain'         => 'required|exists:Terrain,id_terrain',
                'nombre_terrains'    => 'sometimes|integer|min:1',
                'montant_mensuel'    => 'sometimes|numeric|min:0',
                'nombre_mensualites' => 'sometimes|integer|min:1',
            ]);

            // Vérifier si l’utilisateur cible est bien de type "user"
            $utilisateur = Utilisateur::find($request->id_utilisateur);
            if (!$utilisateur || $utilisateur->type !== 'user') {
                return $this->responseError("L'utilisateur doit être de type 'user' pour créer une souscription.", 403);
            }

             $terrain = Terrain::find($request->id_terrain);
             if (!$terrain ) {
                return $this->responseError("Le terrain non trouver  pour créer une souscription.", 403);   
                }

            // Création de la souscription (on ajoute l'admin connecté automatiquement)
            $souscription = Souscription::create([
                'id_utilisateur' => $request->id_utilisateur,
                'montant_mensuel'     => $terrain->montant_mensuel,
                'id_terrain'     => $request->id_terrain,
                'id_admin'       => $user->id_utilisateur,
            ]);


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
