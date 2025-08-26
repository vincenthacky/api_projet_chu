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
                    'nom_original'=> $fichier->getClientOriginalName(),
                    'chemin_fichier' => $chemin,
                    'taille_fichier' => $fichier->getSize(),
                ]);
            }

            return Document::create($documentData);
        });
    }


}
