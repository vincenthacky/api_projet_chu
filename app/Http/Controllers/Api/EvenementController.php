<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeEvenement;
use App\Models\Evenement;
use Carbon\Carbon;
use Exception;

use Illuminate\Http\Request;

class EvenementController extends Controller
{
//    public function index(Request $request)
//     {
//         try {
//             $perPage = $request->input('per_page', 15);
//             $search  = $request->input('search');
//             $idSouscription = $request->input('id_souscription');

//             // Récupérer tous les types d'événement
//             $types = TypeEvenement::orderBy('ordre_affichage')->get();
//             $result = [];

//             foreach ($types as $type) {
//                 $query = Evenement::with(['documents'])
//                     ->where('id_type_evenement', $type->id_type_evenement)
//                     ->where(function($q) use ($idSouscription) {
//                         $q->where('id_souscription', request()->input('id_souscription'))
//                           ->orWhere(function($q2) {
//                               $q2->whereNull('id_souscription')
//                                  ->where('est_public', 1);
//                           });
//                     });

//                 if ($search) {
//                     $query->where(function($q) use ($search) {
//                         $q->where('titre', 'like', "%{$search}%")
//                           ->orWhere('description', 'like', "%{$search}%")
//                           ->orWhere('lieu', 'like', "%{$search}%");
//                     });
//                 }

//                 $evenementsSouscription = $query->orderBy('date_debut_evenement')
//                                                 ->paginate($perPage);

//                 // Regroupement par mois/année
//                 $evenementsSouscription->getCollection()->transform(function($event){
//                     $event->documents_lies = $event->documents->groupBy('type_fichier'); // image, video, document
//                     unset($event->documents);
//                     $event->mois_annee = Carbon::parse($event->date_debut_evenement)->format('F Y');
//                     return $event;
//                 });

//                 $result[] = [
//                     'type_evenement' => $type->libelle_type_evenement,
//                     'evenements' => $evenementsSouscription
//                 ];
//             }

//             return $this->responseSuccessPaginate(collect($result), "Liste des événements");

//         } catch (Exception $e) {
//             return $this->responseError("Erreur lors de la récupération des événements : " . $e->getMessage(), 500);
//         }
//     }


    /**
     * Liste paginée des événements regroupés par type et mois.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
            $idSouscription = $request->input('id_souscription');

            // Récupérer tous les types d'événement
            $types = TypeEvenement::orderBy('ordre_affichage')->get();
            $result = [];

            foreach ($types as $type) {
                $query = Evenement::with(['documents'])
                    ->where('id_type_evenement', $type->id_type_evenement)
                    ->where(function ($q) use ($idSouscription) {
                        $q->where('id_souscription', $idSouscription)
                          ->orWhere(function ($q2) {
                              $q2->whereNull('id_souscription')
                                 ->where('est_public', 1);
                          });
                    });

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('titre', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('lieu', 'like', "%{$search}%");
                    });
                }

                // Pagination
                $evenementsPagines = $query->orderBy('date_debut_evenement')
                                           ->paginate($perPage);

                // Transformer chaque événement pour regrouper les documents par type et ajouter mois/année
                $evenementsPagines->getCollection()->transform(function ($event) {
                    $event->documents_lies = $event->documents->groupBy('type_fichier'); // ex: image, video, document
                    unset($event->documents);
                    $event->mois_annee = Carbon::parse($event->date_debut_evenement)->format('F Y');
                    return $event;
                });

                $result[] = [
                    'type_evenement' => $type->libelle_type_evenement,
                    'evenements' => $evenementsPagines
                ];
            }

            return $this->responseSuccessPaginate(collect($result), "Liste des événements");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des événements : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un événement spécifique par ID
     */
    public function show($id)
    {
        try {
            $evenement = Evenement::with(['documents', 'typeEvenement'])
                ->findOrFail($id);

            $evenement->documents_lies = $evenement->documents->groupBy('type_fichier');
            unset($evenement->documents);
            $evenement->mois_annee = Carbon::parse($evenement->date_debut_evenement)->format('F Y');

            return $this->responseSuccess($evenement, "Événement récupéré avec succès");

        } catch (Exception $e) {
            return $this->responseError("Événement introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un nouvel événement
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_type_evenement' => 'required|exists:TypeEvenement,id_type_evenement',
                'id_souscription' => 'nullable|exists:Souscription,id_souscription',
                'titre' => 'required|string|max:255',
                'description' => 'required|string',
                'date_debut_evenement' => 'required|date',
                'date_fin_evenement' => 'nullable|date',
                'lieu' => 'nullable|string|max:255',
                'est_public' => 'nullable|boolean',
            ]);

            $evenement = Evenement::create($request->all());

            return $this->responseSuccessMessage("Événement créé avec succès", 201);

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la création de l'événement : " . $e->getMessage(), 500);
        }
    }

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
