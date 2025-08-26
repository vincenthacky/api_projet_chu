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
     * Liste paginée des événements regroupés par type et mois.
     */
     public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
            $idSouscription = $request->input('id_souscription');

            $query = Evenement::with(['documents.typeDocument', 'typeEvenement'])
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

            $evenementsPagines = $query->orderBy('date_debut_evenement')
                                    ->paginate($perPage);

            // $evenementsPagines->getCollection()->transform(function ($event) {
            //     $event->documents_lies = $event->documents;
            //     unset($event->documents);
            //     $event->mois_annee = Carbon::parse($event->date_debut_evenement)->format('F Y');
            //     return $event;
            // });

            return $this->responseSuccessPaginate($evenementsPagines, "Liste des événements");

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
