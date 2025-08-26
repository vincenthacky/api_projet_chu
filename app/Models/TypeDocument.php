<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeDocument extends Model
{
    use HasFactory;

    /**
     * Nom de la table (important car le nom a une majuscule).
     */
    protected $table = 'TypeDocument';

    /**
     * Clé primaire de la table.
     */
    protected $primaryKey = 'id_type_document';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire.
     */
    protected $keyType = 'int';

    /**
     * Pas de colonnes created_at / updated_at.
     */
    public $timestamps = false;

    /**
     * Champs autorisés pour l’assignation de masse.
     */
    protected $fillable = [
        'libelle_type_document',
        'description_type',
        'extension_autorisee',
        'taille_max_mo',
        'est_obligatoire',
    ];

    /**
     * Casting automatique des colonnes.
     */
    protected $casts = [
        'est_obligatoire' => 'boolean',
        'taille_max_mo'   => 'integer',
    ];

    /**
     * Valeurs par défaut.
     */
    protected $attributes = [
        'extension_autorisee' => 'pdf,jpg,jpeg,png',
        'taille_max_mo'       => 5,
        'est_obligatoire'     => false,
    ];

    /**
     * Relations
     */

    // Un type de document peut être lié à plusieurs documents
    public function documents()
    {
        return $this->hasMany(Document::class, 'id_type_document', 'id_type_document');
    }

    /**
     * Accessors / Mutators
     */

    // Retourne les extensions sous forme de tableau
    public function getExtensionsAttribute()
    {
        return explode(',', $this->extension_autorisee);
    }

    // Vérifie si une extension donnée est autorisée
    public function extensionAutorisee($extension): bool
    {
        return in_array(strtolower($extension), $this->extensions);
    }

    // Taille max en octets (pratique pour validation upload)
    public function getTailleMaxOctetsAttribute()
    {
        return $this->taille_max_mo * 1024 * 1024;
    }
}
