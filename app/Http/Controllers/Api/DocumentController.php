<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DocumentService;
use App\Models\Document;
use App\Models\Utilisateur;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Exception;

class DocumentController extends Controller
{

    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Récupérer tous les documents avec pagination et recherche.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');

            $query = Document::with(['souscription', 'typeDocument']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom_fichier', 'like', "%{$search}%")
                      ->orWhere('nom_original', 'like', "%{$search}%")
                      ->orWhere('chemin_fichier', 'like', "%{$search}%")
                      ->orWhere('description_document', 'like', "%{$search}%")
                    
                      ->orWhereHas('typeDocument', function ($q3) use ($search) {
                          $q3->where('libelle_type_document', 'like', "%{$search}%");
                      });
                });
            }

            $documents = $query->orderBy('date_telechargement', 'desc')
                               ->paginate($perPage);

            return $this->responseSuccessPaginate($documents, "Liste des documents");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des documents : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer tous les documents avec pagination et recherche.
     */
    public function indexUtilisateur(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search');
            $user = JWTAuth::parseToken()->authenticate();

           $query = Document::with(['souscription', 'typeDocument'])
            ->whereHas('souscription', function ($q) use ($user) {
                $q->where('id_utilisateur', $user->id_utilisateur);
            });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom_fichier', 'like', "%{$search}%")
                      ->orWhere('nom_original', 'like', "%{$search}%")
                      ->orWhere('chemin_fichier', 'like', "%{$search}%")
                      ->orWhere('description_document', 'like', "%{$search}%")
                     
                      ->orWhereHas('typeDocument', function ($q3) use ($search) {
                          $q3->where('libelle_type_document', 'like', "%{$search}%");
                      });
                });
            }

            $documents = $query->orderBy('date_telechargement', 'desc')
                               ->paginate($perPage);

            return $this->responseSuccessPaginate($documents, "Liste des documents");

        } catch (Exception $e) {
            return $this->responseError("Erreur lors de la récupération des documents : " . $e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un seul document
     */
    public function show($id)
    {
        try {
            $document = Document::with(['souscription', 'typeDocument'])->findOrFail($id);

            return $this->responseSuccess($document, "Document récupéré");
        } catch (Exception $e) {
            return $this->responseError("Document introuvable ou erreur : " . $e->getMessage(), 404);
        }
    }

    /**
     * Créer un nouveau document
     */
        public function store(Request $request)
    {
        $validated = $request->validate([
            'id_souscription'       => 'required|exists:Souscription,id_souscription',
            'libelle_type_document' => 'required|string|max:100',
            'description_type'      => 'nullable|string',
            'extension_autorisee'   => 'nullable|string',
            'taille_max_mo'         => 'nullable|integer',
            'est_obligatoire'       => 'nullable|boolean',
            'fichier'               => 'nullable|file',
            'source_table'          => 'nullable|string',
            'source_id'             => 'nullable|integer',
            'description_document'  => 'nullable|string',
        ]);

        try {
            $document = $this->documentService->store(
                $validated['id_souscription'],
                $validated['libelle_type_document'],
                collect($validated)->except(['id_souscription','libelle_type_document','fichier'])->toArray(),
                $request->file('fichier')
            );

            return $this->responseSuccessMessage("Document enregistré avec succès", $document);

        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    /**
     * Créer un nouveau document lié à un utilisateur et une souscription
     */
    public function storeDossierUtilisateur(Request $request)
    {
        $validated = $request->validate([
            'id_utilisateur'         => 'required|exists:Utilisateur,id_utilisateur',
            'id_souscription'        => 'required|exists:Souscription,id_souscription',
            'libelle_type_document'  => 'required|string|max:100',
            'document'                => 'required|file',
        ]);

        try {
            $user = Utilisateur::findOrFail($validated['id_utilisateur']);
            $libelle = $validated['libelle_type_document'];

            $options = [
                'id_utilisateur'       => $user->id_utilisateur,
                'source_table'         => 'utilisateurs',
                'source_id'            => $user->id_utilisateur,
                'description_document' => "Document {$libelle} de l'utilisateur {$user->nom} {$user->prenom}",
            ];

            $document = $this->documentService->store(
                idSouscription: $validated['id_souscription'],
                libelleTypeDocument: $libelle,
                options: $options,
                fichier: $request->file('document')
            );

            return $this->responseSuccessMessage("Document enregistré avec succès", 200);

        } catch (\Exception $e) {
            return $this->responseError("Erreur lors de l'enregistrement : " . $e->getMessage(),500);
        }
    }



    /**
     * Mettre à jour un document
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $document = Document::findOrFail($id);

            $request->validate([
                'nom_fichier'         => 'sometimes|required|string|max:255',
                'nom_original'        => 'sometimes|required|string|max:255',
                'chemin_fichier'      => 'sometimes|required|string|max:500',
                'type_mime'           => 'sometimes|required|string|max:100',
                'taille_fichier'      => 'sometimes|required|integer|min:1',
                'description_document'=> 'nullable|string',
                'version_document'    => 'nullable|integer|min:1',
                'statut_document'     => 'in:actif,archive,supprime',
            ]);

            $document->update($request->all());

            DB::commit();

            return $this->responseSuccessMessage("Document mis à jour avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la mise à jour du document : " . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un document
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $document = Document::findOrFail($id);
            $document->delete();

            DB::commit();

            return $this->responseSuccessMessage("Document supprimé avec succès");

        } catch (Exception $e) {
            DB::rollback();
            return $this->responseError("Erreur lors de la suppression du document : " . $e->getMessage(), 500);
        }
    }
}
