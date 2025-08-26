<?php

namespace App\Services;



use App\Models\Document;
use App\Models\TypeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;


class DocumentService
{

     /**
     * Enregistre un document et retourne le modèle créé.
     */
    public function store(
        int $idSouscription,
        string $libelleTypeDocument,
        array $options = [],
        ?UploadedFile $fichier = null
    ): Document {
        return DB::transaction(function () use ($idSouscription, $libelleTypeDocument, $options, $fichier) {
            // Création ou récupération du type de document
            $typeDocument = TypeDocument::firstOrCreate(
                ['libelle_type_document' => $libelleTypeDocument],
                [
                    'description_type'    => $options['description_type'] ?? null,
                    'extension_autorisee' => $options['extension_autorisee'] ?? 'pdf,jpg,jpeg,png',
                    'taille_max_mo'       => $options['taille_max_mo'] ?? 5,
                    'est_obligatoire'     => $options['est_obligatoire'] ?? false,
                ]
            );

            $documentData = [
                'id_souscription'      => $idSouscription,
                'id_type_document'     => $typeDocument->id_type_document,
                'source_table'         => $options['source_table'] ?? null,
                'id_source'            => $options['source_id'] ?? null,
                'description_document' => $options['description_document'] ?? $libelleTypeDocument,
            ];

            if ($fichier) {
                // Vérification extension autorisée
                if (!$typeDocument->extensionAutorisee($fichier->getClientOriginalExtension())) {
                    throw new Exception("Extension non autorisée. Autorisées : " . implode(',', $typeDocument->extensions));
                }

                // Vérification taille max
                if ($fichier->getSize() > $typeDocument->tailleMaxOctets) {
                    throw new Exception("Le fichier dépasse la taille maximale de {$typeDocument->taille_max_mo} Mo.");
                }

                $chemin = $fichier->store('documents', 'public');

                $documentData = array_merge($documentData, [
                    'nom_fichier'    => $fichier->getClientOriginalName(),
                    'chemin_fichier' => $chemin,
                    'taille_fichier' => $fichier->getSize(),
                ]);
            }

            return Document::create($documentData);
        });
    }


 
    // public function store(Request $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         // Validation
    //         $request->validate([
    //             'id_souscription'       => 'required|exists:Souscription,id_souscription',
    //             'libelle_type_document' => 'required|string|max:100',
    //             'description_type'      => 'nullable|string',
    //             'extension_autorisee'   => 'nullable|string',
    //             'taille_max_mo'         => 'nullable|integer',
    //             'est_obligatoire'       => 'nullable|boolean',
    //             'fichier'               => 'required|file', // rendre obligatoire si tu veux créer un document
    //             'source_table'          => 'nullable|string',
    //             'source_id'             => 'nullable|integer',
    //             'description_document'  => 'nullable|string',
    //         ]);

    //         // Création ou récupération du type de document
    //         $typeDocument = TypeDocument::firstOrCreate(
    //             ['libelle_type_document' => $request->libelle_type_document],
    //             [
    //                 'description_type'    => $request->description_type,
    //                 'extension_autorisee' => $request->extension_autorisee ?? 'pdf,jpg,jpeg,png',
    //                 'taille_max_mo'       => $request->taille_max_mo ?? 5,
    //                 'est_obligatoire'     => $request->est_obligatoire ?? false,
    //             ]
    //         );

    //         $documentData = [];

    //         if ($request->hasFile('fichier')) {
    //             $fichier = $request->file('fichier');

    //             // Vérification extension autorisée
    //             if (!$typeDocument->extensionAutorisee($fichier->getClientOriginalExtension())) {
    //                 return $this->responseError("Extension de fichier non autorisée. Extensions autorisées : " . implode(',', $typeDocument->extensions));
    //             }

    //             // Vérification taille max
    //             if ($fichier->getSize() > $typeDocument->tailleMaxOctets) {
    //                 return $this->responseError("Le fichier dépasse la taille maximale autorisée de {$typeDocument->taille_max_mo} Mo.");
    //             }

    //             // Stockage du fichier
    //             $chemin = $fichier->store('documents', 'public');

    //             $documentData = [
    //                 'nom_fichier'    => $fichier->getClientOriginalName(),
    //                 'chemin_fichier' => $chemin,
    //                 'taille_fichier' => $fichier->getSize(),
    //             ];
    //         }

    //         // Enregistrement du document
    //         $document = Document::create(array_merge([
    //             'id_souscription'     => $request->id_souscription,
    //             'id_type_document'    => $typeDocument->id_type_document,
    //             'source_table'        => $request->source_table ?? null,
    //             'id_source'           => $request->source_id ?? null,
    //             'description_document'=> $request->description_document ?? $request->libelle_type_document ?? 'un fichier quelconque',
    //         ], $documentData));

    //         DB::commit();
    //         return $this->responseSuccessMessage("Document enregistré avec succès", $document);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->responseError("Erreur lors de l'enregistrement du document : " . $e->getMessage());
    //     }
    // }

}
