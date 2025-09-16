<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Souscription;
use App\Models\PlanPaiement;
use App\Models\Evenement;
use App\Models\Reclamation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatistiqueController extends Controller
{
    /**
     * API 1: Statistiques générales du dashboard (stats object)
     * GET /api/dashboard/stats
     */
    public function getStats(Request $request)
    {
        try {
            // Calcul des statistiques principales
            $totalSouscriptions = Souscription::count();
            $souscriptionsActives = Souscription::where('statut_souscription', Souscription::STATUT_ACTIVE)->count();
            
            $totalPaiements = PlanPaiement::count();
            $paiementsEnRetard = PlanPaiement::where('statut_versement', PlanPaiement::STATUT_NON_PAYE)
                ->where('date_limite_versement', '<', Carbon::now())
                ->count();
            
            $totalReclamations = Reclamation::count();
            $reclamationsEnCours = Reclamation::join('StatutReclamation', 'Reclamation.id_statut_reclamation', '=', 'StatutReclamation.id_statut_reclamation')
                ->whereIn('StatutReclamation.libelle_statut_reclamation', ['En cours', 'En attente'])
                ->count();
            
            $totalEvenements = Evenement::where('actif', 1)->count();
            $evenementsEnCours = Evenement::where('statut_evenement', 'en_cours')
                ->where('actif', 1)
                ->count();
            
            $montantTotalCollecte = PlanPaiement::where('est_paye', true)->sum('montant_paye');
            $montantTotal = PlanPaiement::sum('montant_versement_prevu');
            $montantRestant = $montantTotal - $montantTotalCollecte;

            $stats = [
                'totalSouscriptions' => (int)$totalSouscriptions,
                'souscriptionsActives' => (int)$souscriptionsActives,
                'totalPaiements' => (int)$totalPaiements,
                'paiementsEnRetard' => (int)$paiementsEnRetard,
                'totalReclamations' => (int)$totalReclamations,
                'reclamationsEnCours' => (int)$reclamationsEnCours,
                'totalEvenements' => (int)$totalEvenements,
                'evenementsEnCours' => (int)$evenementsEnCours,
                'montantTotalCollecte' => (float)$montantTotalCollecte,
                'montantRestant' => (float)$montantRestant
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 2: Données pour le graphique des paiements (doughnut)
     * GET /api/dashboard/paiements-chart
     */
    public function getPaiementsChart(Request $request)
    {
        $year = $request->get('year', date('Y'));

        try {
            $paiementsStats = PlanPaiement::selectRaw('
                SUM(CASE WHEN statut_versement = "paye_a_temps" THEN 1 ELSE 0 END) as payes_a_temps,
                SUM(CASE WHEN statut_versement = "paye_en_retard" THEN 1 ELSE 0 END) as payes_en_retard,
                SUM(CASE WHEN statut_versement = "en_attente" THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN statut_versement = "non_paye" THEN 1 ELSE 0 END) as non_payes,
                COUNT(*) as total
            ')
            ->whereYear('date_limite_versement', $year)
            ->first();

            $total = $paiementsStats->total;
            
            // Calcul des pourcentages
            $data = [
                'labels' => ['Payés à temps', 'Payés en retard', 'En attente', 'Non payés'],
                'datasets' => [
                    [
                        'data' => [
                            $total > 0 ? round(($paiementsStats->payes_a_temps / $total) * 100, 1) : 0,
                            $total > 0 ? round(($paiementsStats->payes_en_retard / $total) * 100, 1) : 0,
                            $total > 0 ? round(($paiementsStats->en_attente / $total) * 100, 1) : 0,
                            $total > 0 ? round(($paiementsStats->non_payes / $total) * 100, 1) : 0
                        ],
                        'backgroundColor' => [
                            '#28a745',  // Vert pour payés à temps
                            '#ffc107',  // Jaune pour payés en retard
                            '#17a2b8',  // Bleu pour en attente
                            '#dc3545'   // Rouge pour non payés
                        ],
                        'borderWidth' => 2
                    ]
                ],
                'counts' => [
                    'payes_a_temps' => (int)$paiementsStats->payes_a_temps,
                    'payes_en_retard' => (int)$paiementsStats->payes_en_retard,
                    'en_attente' => (int)$paiementsStats->en_attente,
                    'non_payes' => (int)$paiementsStats->non_payes,
                    'total' => (int)$total
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'year' => $year
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 3: Données pour le graphique des souscriptions (bar chart)
     * GET /api/dashboard/souscriptions-chart
     */
    public function getSouscriptionsChart(Request $request)
    {
        $year = $request->get('year', date('Y'));

        try {
            $souscriptionsData = Souscription::select(
                DB::raw('MONTH(date_souscription) as mois'),
                DB::raw('COUNT(*) as nombre')
            )
            ->whereYear('date_souscription', $year)
            ->groupBy(DB::raw('MONTH(date_souscription)'))
            ->orderBy('mois')
            ->get();

            $moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            
            // Préparer les données pour les 12 mois
            $labels = [];
            $data = [];
            
            for ($i = 1; $i <= 12; $i++) {
                $labels[] = $moisNoms[$i - 1];
                $monthData = $souscriptionsData->firstWhere('mois', $i);
                $data[] = $monthData ? (int)$monthData->nombre : 0;
            }

            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Nouvelles souscriptions',
                        'data' => $data,
                        'backgroundColor' => '#007bff',
                        'borderColor' => '#0056b3',
                        'borderWidth' => 1
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $chartData,
                'year' => $year,
                'total' => array_sum($data)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de souscriptions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 4: Données pour le graphique des événements (line chart)
     * GET /api/dashboard/evenements-chart
     */
    public function getEvenementsChart(Request $request)
    {
        $year = $request->get('year', date('Y'));

        try {
            $evenementsData = Evenement::select(
                DB::raw('MONTH(date_fin_evenement) as mois'),
                DB::raw('COUNT(*) as nombre')
            )
            ->whereYear('date_fin_evenement', $year)
            ->where('statut_evenement', 'termine')
            ->where('actif', 1)
            ->groupBy(DB::raw('MONTH(date_fin_evenement)'))
            ->orderBy('mois')
            ->get();

            $moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            
            $labels = [];
            $data = [];
            
            for ($i = 1; $i <= 12; $i++) {
                $labels[] = $moisNoms[$i - 1];
                $monthData = $evenementsData->firstWhere('mois', $i);
                $data[] = $monthData ? (int)$monthData->nombre : 0;
            }

            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Événements réalisés',
                        'data' => $data,
                        'borderColor' => '#28a745',
                        'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                        'tension' => 0.4,
                        'fill' => true
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $chartData,
                'year' => $year,
                'total' => array_sum($data)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données d\'événements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 5: Données pour le graphique des réclamations (pie chart)
     * GET /api/dashboard/reclamations-chart
     */
    public function getReclamationsChart(Request $request)
    {
        $year = $request->get('year', date('Y'));

        try {
            $reclamationsData = Reclamation::join('StatutReclamation', 'Reclamation.id_statut_reclamation', '=', 'StatutReclamation.id_statut_reclamation')
                ->select(
                    'StatutReclamation.nom_statut',
                    DB::raw('COUNT(*) as nombre')
                )
                ->whereYear('Reclamation.date_reclamation', $year)
                ->groupBy('StatutReclamation.nom_statut')
                ->get();

            // Mapping des statuts pour correspondre à l'interface
            $statusMapping = [
                'Résolue' => 'Résolues',
                'En cours' => 'En cours', 
                'En attente' => 'En attente',
                'Rejetée' => 'Rejetées'
            ];

            $labels = ['Résolues', 'En cours', 'En attente', 'Rejetées'];
            $data = [0, 0, 0, 0]; // Initialiser à 0
            
            foreach ($reclamationsData as $item) {
                $status = $item->libelle_statut_reclamation;
                $index = array_search($statusMapping[$status] ?? $status, $labels);
                if ($index !== false) {
                    $data[$index] = (int)$item->nombre;
                }
            }

            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => [
                            '#28a745',  // Vert pour résolues
                            '#ffc107',  // Jaune pour en cours
                            '#17a2b8',  // Bleu pour en attente
                            '#dc3545'   // Rouge pour rejetées
                        ],
                        'borderWidth' => 2
                    ]
                ],
                'counts' => [
                    'resolues' => $data[0],
                    'en_cours' => $data[1], 
                    'en_attente' => $data[2],
                    'rejetees' => $data[3],
                    'total' => array_sum($data)
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $chartData,
                'year' => $year
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de réclamations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 6: Activités récentes
     * GET /api/dashboard/recent-activities
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->get('limit', 10);

        try {
            $activities = collect();

            // Nouveaux paiements (derniers 7 jours)
            $paiements = PlanPaiement::where('date_paiement_effectif', '>=', Carbon::now()->subDays(7))
                ->where('est_paye', true)
                ->with('souscription.utilisateur')
                ->orderBy('date_paiement_effectif', 'desc')
                ->limit(3)
                ->get();

            foreach ($paiements as $paiement) {
                $nomUtilisateur = $paiement->souscription->utilisateur->nom_complet ?? 'Utilisateur';
                $activities->push([
                    'type' => 'paiement',
                    'message' => "Nouveau paiement reçu de {$nomUtilisateur}",
                    'time' => Carbon::parse($paiement->date_paiement_effectif)->diffForHumans(),
                    'status' => 'success'
                ]);
            }

            // Nouvelles réclamations (derniers 7 jours)
            $reclamations = Reclamation::where('date_reclamation', '>=', Carbon::now()->subDays(7))
                ->with('souscription.utilisateur')
                ->orderBy('date_reclamation', 'desc')
                ->limit(3)
                ->get();

            foreach ($reclamations as $reclamation) {
                $nomUtilisateur = $reclamation->souscription->utilisateur->nom_complet ?? 'Utilisateur';
                $activities->push([
                    'type' => 'reclamation',
                    'message' => "Réclamation de {$nomUtilisateur}",
                    'time' => Carbon::parse($reclamation->date_reclamation)->diffForHumans(),
                    'status' => 'warning'
                ]);
            }

            // Nouveaux événements (derniers 7 jours)
            $evenements = Evenement::where('date_creation', '>=', Carbon::now()->subDays(7))
                ->where('actif', 1)
                ->orderBy('date_creation', 'desc')
                ->limit(3)
                ->get();

            foreach ($evenements as $evenement) {
                $activities->push([
                    'type' => 'evenement',
                    'message' => "Nouvel événement: {$evenement->titre}",
                    'time' => Carbon::parse($evenement->date_creation)->diffForHumans(),
                    'status' => 'info'
                ]);
            }

            // Nouvelles souscriptions (derniers 7 jours)
            $souscriptions = Souscription::where('date_souscription', '>=', Carbon::now()->subDays(7))
                ->with('utilisateur')
                ->orderBy('date_souscription', 'desc')
                ->limit(3)
                ->get();

            foreach ($souscriptions as $souscription) {
                $nomUtilisateur = $souscription->utilisateur->nom_complet ?? 'Utilisateur';
                $activities->push([
                    'type' => 'souscription',
                    'message' => "Nouvelle souscription de {$nomUtilisateur}",
                    'time' => Carbon::parse($souscription->date_souscription)->diffForHumans(),
                    'status' => 'success'
                ]);
            }

            // Trier par date et limiter
            $sortedActivities = $activities->sortByDesc(function($activity) {
                // Convertir le temps relatif en timestamp pour trier
                return Carbon::now()->subMinutes($this->getMinutesFromRelativeTime($activity['time']));
            })->take($limit)->values();

            return response()->json([
                'success' => true,
                'data' => $sortedActivities->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des activités récentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 7: Alertes importantes
     * GET /api/dashboard/alertes
     */
    public function getAlertes(Request $request)
    {
        try {
            $alertes = [];

            // Paiements en retard
            $paiementsEnRetard = PlanPaiement::where('statut_versement', PlanPaiement::STATUT_NON_PAYE)
                ->where('date_limite_versement', '<', Carbon::now())
                ->count();

            if ($paiementsEnRetard > 0) {
                $alertes[] = [
                    'type' => 'danger',
                    'message' => $paiementsEnRetard . ' paiements en retard nécessitent une attention',
                    'count' => (int)$paiementsEnRetard
                ];
            }

            // Réclamations en attente
            $reclamationsEnAttente = Reclamation::join('StatutReclamation', 'Reclamation.id_statut_reclamation', '=', 'StatutReclamation.id_statut_reclamation')
                ->whereIn('StatutReclamation.libelle_statut_reclamation', ['En attente', 'En cours'])
                ->count();

            if ($reclamationsEnAttente > 0) {
                $alertes[] = [
                    'type' => 'warning',
                    'message' => $reclamationsEnAttente . ' réclamations en attente de traitement',
                    'count' => (int)$reclamationsEnAttente
                ];
            }

            // Événements cette semaine
            $evenementsCetteSemaine = Evenement::whereBetween('date_debut_evenement', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->where('actif', 1)
            ->count();

            if ($evenementsCetteSemaine > 0) {
                $alertes[] = [
                    'type' => 'info',
                    'message' => $evenementsCetteSemaine . ' événements prévus cette semaine',
                    'count' => (int)$evenementsCetteSemaine
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $alertes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des alertes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API 8: Données complètes du dashboard (pour initialiser tous les éléments)
     * GET /api/dashboard/complete
     */
    public function getDashboardComplete(Request $request)
    {
        try {
            $year = $request->get('year', date('Y'));
            
            $data = [
                'stats' => $this->getStatsData(),
                'paiementsChart' => $this->getPaiementsChartData($year),
                'souscriptionsChart' => $this->getSouscriptionsChartData($year),
                'evenementsChart' => $this->getEvenementsChartData($year),
                'reclamationsChart' => $this->getReclamationsChartData($year),
                'recentActivities' => $this->getRecentActivitiesData(),
                'alertes' => $this->getAlertesData()
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'year' => $year,
                'generated_at' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération complète du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========== MÉTHODES PRIVÉES ==========

    private function getStatsData()
    {
        // Réutiliser la logique de getStats mais retourner directement les données
        $totalSouscriptions = Souscription::count();
        $souscriptionsActives = Souscription::where('statut_souscription', Souscription::STATUT_ACTIVE)->count();
        
        $totalPaiements = PlanPaiement::count();
        $paiementsEnRetard = PlanPaiement::where('statut_versement', PlanPaiement::STATUT_NON_PAYE)
            ->where('date_limite_versement', '<', Carbon::now())
            ->count();
        
        $totalReclamations = Reclamation::count();
        $reclamationsEnCours = Reclamation::join('StatutReclamation', 'Reclamation.id_statut_reclamation', '=', 'StatutReclamation.id_statut_reclamation')
            ->whereIn('StatutReclamation.libelle_statut_reclamation', ['En cours', 'En attente'])
            ->count();
        
        $totalEvenements = Evenement::where('actif', 1)->count();
        $evenementsEnCours = Evenement::where('statut_evenement', 'en_cours')
            ->where('actif', 1)
            ->count();
        
        $montantTotalCollecte = PlanPaiement::where('est_paye', true)->sum('montant_paye');
        $montantTotal = PlanPaiement::sum('montant_versement_prevu');
        $montantRestant = $montantTotal - $montantTotalCollecte;

        return [
            'totalSouscriptions' => (int)$totalSouscriptions,
            'souscriptionsActives' => (int)$souscriptionsActives,
            'totalPaiements' => (int)$totalPaiements,
            'paiementsEnRetard' => (int)$paiementsEnRetard,
            'totalReclamations' => (int)$totalReclamations,
            'reclamationsEnCours' => (int)$reclamationsEnCours,
            'totalEvenements' => (int)$totalEvenements,
            'evenementsEnCours' => (int)$evenementsEnCours,
            'montantTotalCollecte' => (float)$montantTotalCollecte,
            'montantRestant' => (float)$montantRestant
        ];
    }

    private function getPaiementsChartData($year)
    {
        // Réutiliser la logique de getPaiementsChart
        $paiementsStats = PlanPaiement::selectRaw('
            SUM(CASE WHEN statut_versement = "paye_a_temps" THEN 1 ELSE 0 END) as payes_a_temps,
            SUM(CASE WHEN statut_versement = "paye_en_retard" THEN 1 ELSE 0 END) as payes_en_retard,
            SUM(CASE WHEN statut_versement = "en_attente" THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN statut_versement = "non_paye" THEN 1 ELSE 0 END) as non_payes,
            COUNT(*) as total
        ')
        ->whereYear('date_limite_versement', $year)
        ->first();

        $total = $paiementsStats->total;
        
        return [
            'labels' => ['Payés à temps', 'Payés en retard', 'En attente', 'Non payés'],
            'datasets' => [
                [
                    'data' => [
                        $total > 0 ? round(($paiementsStats->payes_a_temps / $total) * 100, 1) : 0,
                        $total > 0 ? round(($paiementsStats->payes_en_retard / $total) * 100, 1) : 0,
                        $total > 0 ? round(($paiementsStats->en_attente / $total) * 100, 1) : 0,
                        $total > 0 ? round(($paiementsStats->non_payes / $total) * 100, 1) : 0
                    ],
                    'backgroundColor' => ['#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                    'borderWidth' => 2
                ]
            ],
            'counts' => [
                'payes_a_temps' => (int)$paiementsStats->payes_a_temps,
                'payes_en_retard' => (int)$paiementsStats->payes_en_retard,
                'en_attente' => (int)$paiementsStats->en_attente,
                'non_payes' => (int)$paiementsStats->non_payes,
                'total' => (int)$total
            ]
        ];
    }

    private function getSouscriptionsChartData($year)
    {
        $souscriptionsData = Souscription::select(
            DB::raw('MONTH(date_souscription) as mois'),
            DB::raw('COUNT(*) as nombre')
        )
        ->whereYear('date_souscription', $year)
        ->groupBy(DB::raw('MONTH(date_souscription)'))
        ->orderBy('mois')
        ->get();

        $moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        
        $labels = [];
        $data = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $moisNoms[$i - 1];
            $monthData = $souscriptionsData->firstWhere('mois', $i);
            $data[] = $monthData ? (int)$monthData->nombre : 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nouvelles souscriptions',
                    'data' => $data,
                    'backgroundColor' => '#007bff',
                    'borderColor' => '#0056b3',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    private function getEvenementsChartData($year)
    {
        $evenementsData = Evenement::select(
            DB::raw('MONTH(date_fin_evenement) as mois'),
            DB::raw('COUNT(*) as nombre')
        )
        ->whereYear('date_fin_evenement', $year)
        ->where('statut_evenement', 'termine')
        ->where('actif', 1)
        ->groupBy(DB::raw('MONTH(date_fin_evenement)'))
        ->orderBy('mois')
        ->get();

        $moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        
        $labels = [];
        $data = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $moisNoms[$i - 1];
            $monthData = $evenementsData->firstWhere('mois', $i);
            $data[] = $monthData ? (int)$monthData->nombre : 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Événements réalisés',
                    'data' => $data,
                    'borderColor' => '#28a745',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                    'tension' => 0.4,
                    'fill' => true
                ]
            ]
        ];
    }

    private function getReclamationsChartData($year)
    {
        $reclamationsData = Reclamation::join('StatutReclamation', 'Reclamation.id_statut_reclamation', '=', 'StatutReclamation.id_statut_reclamation')
            ->select(
                'StatutReclamation.libelle_statut_reclamation',
                DB::raw('COUNT(*) as nombre')
            )
            ->whereYear('Reclamation.date_reclamation', $year)
            ->groupBy('StatutReclamation.libelle_statut_reclamation')
            ->get();

        $statusMapping = [
            'Résolue' => 0,
            'En cours' => 1, 
            'En attente' => 2,
            'Rejetée' => 3
        ];

        $labels = ['Résolues', 'En cours', 'En attente', 'Rejetées'];
        $data = [0, 0, 0, 0];
        
        foreach ($reclamationsData as $item) {
            $status = $item->libelle_statut_reclamation;
            if (isset($statusMapping[$status])) {
                $data[$statusMapping[$status]] = (int)$item->nombre;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => ['#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                    'borderWidth' => 2
                ]
            ],
            'counts' => [
                'resolues' => $data[0],
                'en_cours' => $data[1], 
                'en_attente' => $data[2],
                'rejetees' => $data[3],
                'total' => array_sum($data)
            ]
        ];
    }

    private function getRecentActivitiesData()
    {
        $activities = collect();

        // Ajouter quelques activités récentes
        $paiements = PlanPaiement::where('date_paiement_effectif', '>=', Carbon::now()->subDays(3))
            ->where('est_paye', true)
            ->with('souscription.utilisateur')
            ->orderBy('date_paiement_effectif', 'desc')
            ->limit(2)
            ->get();

        foreach ($paiements as $paiement) {
            $nomUtilisateur = $paiement->souscription->utilisateur->nom_complet ?? 'Utilisateur';
            $activities->push([
                'type' => 'paiement',
                'message' => "Nouveau paiement reçu de {$nomUtilisateur}",
                'time' => Carbon::parse($paiement->date_paiement_effectif)->diffForHumans(),
                'status' => 'success'
            ]);
        }

        $reclamations = Reclamation::where('date_reclamation', '>=', Carbon::now()->subDays(3))
            ->with('souscription.utilisateur')
            ->orderBy('date_reclamation', 'desc')
            ->limit(2)
            ->get();

        foreach ($reclamations as $reclamation) {
            $nomUtilisateur = $reclamation->souscription->utilisateur->nom_complet ?? 'Utilisateur';
            $activities->push([
                'type' => 'reclamation',
                'message' => "Réclamation de {$nomUtilisateur}",
                'time' => Carbon::parse($reclamation->date_reclamation)->diffForHumans(),
                'status' => 'warning'
            ]);
        }

        return $activities->take(4)->values()->toArray();
    }

    private function getAlertesData()
    {
        $alertes = [];

        $paiementsEnRetard = PlanPaiement::where('statut_versement', PlanPaiement::STATUT_NON_PAYE)
            ->where('date_limite_versement', '<', Carbon::now())
            ->count();

        if ($paiementsEnRetard > 0) {
            $alertes[] = [
                'type' => 'danger',
                'message' => $paiementsEnRetard . ' paiements en retard nécessitent une attention',
                'count' => (int)$paiementsEnRetard
            ];
        }

        $reclamationsEnAttente = Reclamation::join('StatutReclamation', 'Reclamation.id_statut_reclamation', '=', 'StatutReclamation.id_statut_reclamation')
            ->whereIn('StatutReclamation.libelle_statut_reclamation', ['En attente', 'En cours'])
            ->count();

        if ($reclamationsEnAttente > 0) {
            $alertes[] = [
                'type' => 'warning',
                'message' => $reclamationsEnAttente . ' réclamations en attente de traitement',
                'count' => (int)$reclamationsEnAttente
            ];
        }

        $evenementsCetteSemaine = Evenement::whereBetween('date_debut_evenement', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])
        ->where('actif', 1)
        ->count();

        if ($evenementsCetteSemaine > 0) {
            $alertes[] = [
                'type' => 'info',
                'message' => $evenementsCetteSemaine . ' événements prévus cette semaine',
                'count' => (int)$evenementsCetteSemaine
            ];
        }

        return $alertes;
    }

    private function getMinutesFromRelativeTime($relativeTime)
    {
        // Fonction utilitaire pour convertir le temps relatif en minutes
        // Pour le tri des activités
        if (strpos($relativeTime, 'heure') !== false) {
            return (int)filter_var($relativeTime, FILTER_SANITIZE_NUMBER_INT) * 60;
        }
        if (strpos($relativeTime, 'jour') !== false) {
            return (int)filter_var($relativeTime, FILTER_SANITIZE_NUMBER_INT) * 24 * 60;
        }
        return 0; // minutes ou temps non reconnu
    }
}
