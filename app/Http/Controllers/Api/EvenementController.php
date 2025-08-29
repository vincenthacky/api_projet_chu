<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use App\Models\TypeEvenement;
use App\Models\Evenement;
use Carbon\Carbon;
use Exception;

use Illuminate\Http\Request;

class EvenementController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }



     /**
     * Affiche la liste des événements organisés par type puis par mois/année
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $typeFilter = $request->input('type_evenement');
            $statutFilter = $request->input('statut');
            $anneeFilter = $request->input('annee');
            $moisFilter = $request->input('mois');

            // Construction de la requête de base
            $query = Evenement::with(['typeEvenement', 'souscription.utilisateur', 'souscription.terrain'])
                ->where('actif', true);

            // Filtres de recherche
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('titre', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('lieu', 'like', "%{$search}%")
                      ->orWhere('entreprise_responsable', 'like', "%{$search}%")
                      ->orWhere('responsable_chantier', 'like', "%{$search}%")
                      ->orWhereHas('typeEvenement', function ($q2) use ($search) {
                          $q2->where('libelle_type_evenement', 'like', "%{$search}%");
                      })
                      ->orWhereHas('souscription.utilisateur', function ($q3) use ($search) {
                          $q3->where('nom', 'like', "%{$search}%")
                             ->orWhere('prenom', 'like', "%{$search}%")
                             ->orWhere('matricule', 'like', "%{$search}%");
                      })
                      ->orWhereHas('souscription.terrain', function ($q4) use ($search) {
                          $q4->where('libelle', 'like', "%{$search}%")
                             ->orWhere('localisation', 'like', "%{$search}%");
                      });
                });
            }

            // Filtre par type d'événement
            if ($typeFilter) {
                $query->where('id_type_evenement', $typeFilter);
            }

            // Filtre par statut
            if ($statutFilter) {
                $query->where('statut_evenement', $statutFilter);
            }

            // Filtre par année
            if ($anneeFilter) {
                $query->whereYear('date_debut_evenement', $anneeFilter);
            }

            // Filtre par mois
            if ($moisFilter && $anneeFilter) {
                $query->whereMonth('date_debut_evenement', $moisFilter);
            }

            // Récupération des événements paginés
            $evenements = $query->orderBy('date_debut_evenement', 'desc')
                               ->paginate($perPage);

            // Organisation des données par type puis par mois/année
            $evenementsOrganises = $this->organiserEvenements($evenements->items());

            // Récupération des types d'événements pour les filtres
            $typesEvenements = TypeEvenement::orderBy('ordre_affichage', 'asc')
                                           ->orderBy('libelle_type_evenement', 'asc')
                                           ->get();

            return $this->responseSuccess([
                'evenements_organises' => $evenementsOrganises,
                'pagination' => [
                    'current_page' => $evenements->currentPage(),
                    'last_page' => $evenements->lastPage(),
                    'per_page' => $evenements->perPage(),
                    'total' => $evenements->total(),
                    'from' => $evenements->firstItem(),
                    'to' => $evenements->lastItem()
                ],
                
                'statistiques' => $this->obtenirStatistiques($evenements->items())
            ], "Liste des événements organisés");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des événements : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche un événement spécifique
     */
    public function show($id)
    {
        try {
            $evenement = Evenement::with([
                'typeEvenement', 
                'souscription.utilisateur', 
                'souscription.terrain',
                'documents'
            ])->find($id);

            if (!$evenement) {
                return $this->responseError("Événement non trouvé", 404);
            }

            // Incrémenter le nombre de vues
            $evenement->increment('nombre_vues');

            // Enrichissement des données
            $evenementEnrichi = $this->enrichirEvenement($evenement);

            return $this->responseSuccess($evenementEnrichi, "Détails de l'événement");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération de l'événement : " . $e->getMessage(), 500);
        }
    }

    /**
     * Organise les événements par type puis par mois/année
     */
    private function organiserEvenements($evenements)
    {
        $evenementsTemporaires = [];

        // Phase 1: Organiser avec clés temporaires pour faciliter les regroupements
        foreach ($evenements as $evenement) {
            $typeId = $evenement->id_type_evenement ?? 0;
            $typeLibelle = $evenement->typeEvenement ? $evenement->typeEvenement->libelle_type_evenement : 'Non catégorisé';
            $date = Carbon::parse($evenement->date_debut_evenement);
            $moisAnnee = $this->getLibelleMoisAnnee($date);
            
            // Initialisation de la structure temporaire si nécessaire
            if (!isset($evenementsTemporaires[$typeId])) {
                $evenementsTemporaires[$typeId] = [
                    'type_info' => $evenement->typeEvenement,
                    'mois' => []
                ];
            }
            
            if (!isset($evenementsTemporaires[$typeId]['mois'][$moisAnnee])) {
                $evenementsTemporaires[$typeId]['mois'][$moisAnnee] = [
                    'libelle' => $moisAnnee,
                    'evenements' => []
                ];
            }

            // Enrichissement de l'événement
            $evenementEnrichi = $this->enrichirEvenement($evenement);
            
            $evenementsTemporaires[$typeId]['mois'][$moisAnnee]['evenements'][] = $evenementEnrichi;
        }

        // Phase 2: Transformer en tableau avec structure fixe
        $evenementsOrganises = [];
        
        foreach ($evenementsTemporaires as $typeData) {
            // Convertir les mois en tableau
            $moisArray = [];
            foreach ($typeData['mois'] as $moisData) {
                $moisArray[] = $moisData;
            }
            
            // Trier les mois par ordre chronologique décroissant
            usort($moisArray, function($a, $b) {
                return $this->comparerMoisAnnee($b['libelle'], $a['libelle']);
            });
            
            $evenementsOrganises[] = [
                'type_info' => $typeData['type_info'],
                'mois' => $moisArray
            ];
        }

        // Trier les types par ordre d'affichage
        usort($evenementsOrganises, function($a, $b) {
            $ordreA = $a['type_info']['ordre_affichage'] ?? 999;
            $ordreB = $b['type_info']['ordre_affichage'] ?? 999;
            
            if ($ordreA === $ordreB) {
                $libelleA = $a['type_info']['libelle_type_evenement'] ?? '';
                $libelleB = $b['type_info']['libelle_type_evenement'] ?? '';
                return strcoll($libelleA, $libelleB);
            }
            
            return $ordreA <=> $ordreB;
        });

        return $evenementsOrganises;
    }

    /**
     * Enrichit un événement avec des données calculées
     */
    private function enrichirEvenement($evenement)
    {
        $dateDebut = Carbon::parse($evenement->date_debut_evenement);
        $dateFin = $evenement->date_fin_evenement ? Carbon::parse($evenement->date_fin_evenement) : null;
        $maintenant = Carbon::now();

        return [
            'id_evenement' => $evenement->id_evenement,
            'titre' => $evenement->titre,
            'description' => $evenement->description,
            'date_debut_evenement' => $evenement->date_debut_evenement,
            'date_fin_evenement' => $evenement->date_fin_evenement,
            'date_prevue_fin' => $evenement->date_prevue_fin,
            'lieu' => $evenement->lieu,
            'coordonnees_gps' => $evenement->coordonnees_gps,
            'statut_evenement' => $evenement->statut_evenement,
            'niveau_avancement_pourcentage' => $evenement->niveau_avancement_pourcentage,
            'etape_actuelle' => $evenement->etape_actuelle,
            'cout_estime' => $evenement->cout_estime,
            'cout_reel' => $evenement->cout_reel,
            'entreprise_responsable' => $evenement->entreprise_responsable,
            'responsable_chantier' => $evenement->responsable_chantier,
            'priorite' => $evenement->priorite,
            'nombre_vues' => $evenement->nombre_vues,
            
            // Relations
            'type_evenement' => $evenement->typeEvenement,
            'souscription' => $evenement->souscription,
            'documents' => $evenement->documents,
            
            // Données calculées
            'duree_estimee_jours' => $dateFin ? $dateDebut->diffInDays($dateFin) : null,
            'jours_depuis_debut' => $dateDebut->isPast() ? $dateDebut->diffInDays($maintenant) : 0,
            'est_en_cours' => $dateDebut->isPast() && ($dateFin ? $dateFin->isFuture() : true),
            'est_termine' => $dateFin ? $dateFin->isPast() : false,
            'est_en_retard' => $evenement->date_prevue_fin && Carbon::parse($evenement->date_prevue_fin)->isPast() && $evenement->statut_evenement !== 'termine',
            'progression_temps' => $this->calculerProgressionTemps($evenement),
            'badge_statut' => $this->getBadgeStatut($evenement),
            'couleur_avancement' => $this->getCouleurAvancement($evenement->niveau_avancement_pourcentage),
            'date_formatee' => $this->formaterPeriode($evenement),
        ];
    }

    /**
     * Génère le libellé du mois/année
     */
    private function getLibelleMoisAnnee($date)
    {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $mois[$date->month] . ' ' . $date->year;
    }

    /**
     * Compare deux libellés mois/année pour le tri chronologique
     */
    private function comparerMoisAnnee($moisAnnee1, $moisAnnee2)
    {
        $moisNoms = [
            'Janvier' => 1, 'Février' => 2, 'Mars' => 3, 'Avril' => 4,
            'Mai' => 5, 'Juin' => 6, 'Juillet' => 7, 'Août' => 8,
            'Septembre' => 9, 'Octobre' => 10, 'Novembre' => 11, 'Décembre' => 12
        ];

        // Extraire mois et année de chaque libellé
        $parts1 = explode(' ', $moisAnnee1);
        $parts2 = explode(' ', $moisAnnee2);
        
        $annee1 = (int)$parts1[1];
        $annee2 = (int)$parts2[1];
        
        $mois1 = $moisNoms[$parts1[0]] ?? 1;
        $mois2 = $moisNoms[$parts2[0]] ?? 1;

        // Comparaison: d'abord par année, puis par mois
        if ($annee1 !== $annee2) {
            return $annee1 <=> $annee2;
        }
        
        return $mois1 <=> $mois2;
    }

    /**
     * Calcule la progression temporelle
     */
    private function calculerProgressionTemps($evenement)
    {
        if (!$evenement->date_debut_evenement || !$evenement->date_fin_evenement) {
            return null;
        }

        $debut = Carbon::parse($evenement->date_debut_evenement);
        $fin = Carbon::parse($evenement->date_fin_evenement);
        $maintenant = Carbon::now();

        if ($maintenant->isBefore($debut)) {
            return 0;
        }

        if ($maintenant->isAfter($fin)) {
            return 100;
        }

        $dureeTotal = $debut->diffInDays($fin);
        $dureeEcoulee = $debut->diffInDays($maintenant);

        return $dureeTotal > 0 ? round(($dureeEcoulee / $dureeTotal) * 100) : 0;
    }

    /**
     * Génère le badge de statut
     */
    private function getBadgeStatut($evenement)
    {
        $statuts = [
            'planifie' => ['libelle' => 'Planifié', 'couleur' => 'info'],
            'en_cours' => ['libelle' => 'En cours', 'couleur' => 'warning'],
            'termine' => ['libelle' => 'Terminé', 'couleur' => 'success'],
            'annule' => ['libelle' => 'Annulé', 'couleur' => 'danger'],
            'reporte' => ['libelle' => 'Reporté', 'couleur' => 'secondary'],
            'suspendu' => ['libelle' => 'Suspendu', 'couleur' => 'dark']
        ];

        return $statuts[$evenement->statut_evenement] ?? ['libelle' => 'Inconnu', 'couleur' => 'secondary'];
    }

    /**
     * Détermine la couleur selon l'avancement
     */
    private function getCouleurAvancement($pourcentage)
    {
        if ($pourcentage >= 80) return 'success';
        if ($pourcentage >= 50) return 'warning';
        if ($pourcentage >= 25) return 'info';
        return 'danger';
    }

    /**
     * Formate la période de l'événement
     */
    private function formaterPeriode($evenement)
    {
        $debut = Carbon::parse($evenement->date_debut_evenement);
        $fin = $evenement->date_fin_evenement ? Carbon::parse($evenement->date_fin_evenement) : null;

        if (!$fin || $debut->format('Y-m-d') === $fin->format('Y-m-d')) {
            return $debut->format('d/m/Y');
        }

        return $debut->format('d/m/Y') . ' - ' . $fin->format('d/m/Y');
    }

    /**
     * Calcule les statistiques des événements
     */
    private function obtenirStatistiques($evenements)
    {
        $total = count($evenements);
        $statistiques = [
            'total_evenements' => $total,
            'par_statut' => [
                'planifie' => 0,
                'en_cours' => 0,
                'termine' => 0,
                'annule' => 0,
                'reporte' => 0,
                'suspendu' => 0
            ],
            'par_type' => [],
            'avancement_moyen' => 0,
            'cout_total_estime' => 0,
            'cout_total_reel' => 0
        ];

        if ($total === 0) {
            return $statistiques;
        }

        $sommeAvancement = 0;
        
        foreach ($evenements as $evenement) {
            // Statistiques par statut
            $statut = $evenement->statut_evenement;
            if (isset($statistiques['par_statut'][$statut])) {
                $statistiques['par_statut'][$statut]++;
            }

            // Statistiques par type
            $typeLibelle = $evenement->typeEvenement ? 
                          $evenement->typeEvenement->libelle_type_evenement : 
                          'Non catégorisé';
            
            if (!isset($statistiques['par_type'][$typeLibelle])) {
                $statistiques['par_type'][$typeLibelle] = 0;
            }
            $statistiques['par_type'][$typeLibelle]++;

            // Avancement moyen
            $sommeAvancement += $evenement->niveau_avancement_pourcentage ?? 0;

            // Coûts
            $statistiques['cout_total_estime'] += (float)($evenement->cout_estime ?? 0);
            $statistiques['cout_total_reel'] += (float)($evenement->cout_reel ?? 0);
        }

        $statistiques['avancement_moyen'] = round($sommeAvancement / $total, 1);

        return $statistiques;
    }

    
    /**
     * Récupérer un événement spécifique par ID
     */
    // public function show($id)
    // {
    //     try {
    //         $evenement = Evenement::with(['documents', 'typeEvenement'])
    //             ->findOrFail($id);

    //         $evenement->documents_lies = $evenement->documents->groupBy('type_fichier');
    //         unset($evenement->documents);
    //         $evenement->mois_annee = Carbon::parse($evenement->date_debut_evenement)->format('F Y');

    //         return $this->responseSuccess($evenement, "Événement récupéré avec succès");

    //     } catch (Exception $e) {
    //         return $this->responseError("Événement introuvable ou erreur : " . $e->getMessage(), 404);
    //     }
    // }

    /**
     * Créer un nouvel événement
     */
      public function store(Request $request)
    {
        try {
            $request->validate([
                'id_type_evenement'   => 'required|exists:TypeEvenement,id_type_evenement',
                'id_souscription'     => 'nullable|exists:Souscription,id_souscription',
                'titre'               => 'required|string|max:255',
                'description'         => 'required|string',
                'date_debut_evenement'=> 'required|date',
                'date_fin_evenement'  => 'nullable|date',
                'lieu'                => 'nullable|string|max:255',
                'est_public'          => 'nullable|boolean',
                'documents.*'         => 'file|mimes:jpg,jpeg,png,mp4,pdf,doc,docx,xlsx,xls|max:10240', // max 10Mo
            ]);

            // Création de l'événement
            $evenement = Evenement::create($request->all());

            // Si des documents/images/vidéos sont attachés
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $fichier) {
                    $this->documentService->store(
                        idSouscription: $request->id_souscription,
                        libelleTypeDocument: 'Événement - ' . $request->titre,
                        options: [
                            'source_table'         => 'evenements',
                            'source_id'            => $evenement->id_evenement,
                            'description_document' => "Fichier lié à l'événement : " . $request->titre,
                        ],
                        fichier: $fichier
                    );
                }
            }

            return $this->responseSuccess($evenement, "Événement créé avec succès", 201);

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la création de l'événement : " . $e->getMessage(), 500);
        }
    }

    // public function store(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'id_type_evenement' => 'required|exists:TypeEvenement,id_type_evenement',
    //             'id_souscription' => 'nullable|exists:Souscription,id_souscription',
    //             'titre' => 'required|string|max:255',
    //             'description' => 'required|string',
    //             'date_debut_evenement' => 'required|date',
    //             'date_fin_evenement' => 'nullable|date',
    //             'lieu' => 'nullable|string|max:255',
    //             'est_public' => 'nullable|boolean',
    //         ]);

    //         $evenement = Evenement::create($request->all());

    //         return $this->responseSuccessMessage("Événement créé avec succès", 201);

    //     } catch (Exception $e) {
    //         return $this->responseError("Erreur lors de la création de l'événement : " . $e->getMessage(), 500);
    //     }
    // }

    /**
     * Mettre à jour un événement existant
     */
    public function update(Request $request, $id)
    {
        try {
            $evenement = Evenement::findOrFail($id);
            $evenement->update($request->all());

            return $this->responseSuccessMessage("Événement mis à jour avec succès");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la mise à jour de l'événement : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un événement
     */
    public function destroy($id)
    {
        try {
            $evenement = Evenement::findOrFail($id);
            $evenement->delete();

            return $this->responseSuccessMessage("Événement supprimé avec succès");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la suppression de l'événement : " . $e->getMessage(), 500);
        }
    }


}
