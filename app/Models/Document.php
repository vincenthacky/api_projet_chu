<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    /**
     * Nom de la table (important car ta table a une majuscule).
     */
    protected $table = 'Document';

    /**
     * Clé primaire de la table.
     */
    protected $primaryKey = 'id_document';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire.
     */
    protected $keyType = 'int';

    /**
     * La table ne possède pas de colonnes created_at / updated_at.
     */
    public $timestamps = false;

    /**
     * Champs autorisés pour l’assignation de masse.
     */
    protected $fillable = [
        'id_souscription',
        'id_type_document',
        'source_table',
        'id_source',
        'nom_fichier',
        'nom_original',
        'chemin_fichier',
        'type_mime',
        'taille_fichier',
        'description_document',
        'version_document',
        'date_telechargement',
        'statut_document',
    ];

    /**
     * Casting automatique des colonnes.
     */
    protected $casts = [
        'date_telechargement' => 'datetime',
        'taille_fichier'      => 'integer',
        'version_document'    => 'integer',
    ];

    /**
     * Valeurs par défaut cohérentes avec la migration SQL.
     */
    protected $attributes = [
        'version_document' => 1,
        'statut_document'  => self::STATUT_ACTIF,
    ];

    /**
     * ✅ Constantes pour les statuts
     */
    public const STATUT_ACTIF    = 'actif';
    public const STATUT_ARCHIVE  = 'archive';
    public const STATUT_SUPPRIME = 'supprime';

    /**
     * Relations
     */

    // Chaque document appartient à une souscription
    public function souscription()
    {
        return $this->belongsTo(Souscription::class, 'id_souscription', 'id_souscription');
    }

    // Relation avec le type de document
    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class, 'id_type_document', 'id_type_document');
    }

    /**
     * Accessors / Mutators
     */

    // Récupérer une taille formatée du fichier (Ko, Mo, Go)
    public function getTailleLisibleAttribute()
    {
        $bytes = $this->taille_fichier;
        if ($bytes < 1024) {
            return $bytes . ' o';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' Ko';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' Mo';
        }
        return round($bytes / 1073741824, 2) . ' Go';
    }

    // Vérifie si le document est actif
    public function getEstActifAttribute()
    {
        return $this->statut_document === self::STATUT_ACTIF;
    }

    // URL publique du fichier (si besoin de servir depuis storage)
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->chemin_fichier);
    }
}
