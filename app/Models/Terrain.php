<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Terrain extends Model
{
    use HasFactory;

    /**
     * Nom de la table (car ton nom n'est pas au pluriel par défaut Laravel).
     */
    protected $table = 'Terrain';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_terrain';

    /**
     * Indique si la clé est auto-incrémentée.
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire.
     */
    protected $keyType = 'int';

    /**
     * Pas de timestamps Laravel (created_at/updated_at), 
     * ta table utilise `date_creation`.
     */
    public $timestamps = true;

    /**
     * Champs modifiables en masse.
     */
    protected $fillable = [
        'libelle',
        'localisation',
        'superficie',
        'prix_unitaire',
        'description',
        'statut_terrain',
        'date_creation',
    ];

    /**
     * Casts automatiques.
     */
    protected $casts = [
        'superficie'     => 'decimal:2',
        'prix_unitaire'  => 'decimal:2',
        'date_creation'  => 'datetime',
    ];

    /**
     * Valeurs par défaut cohérentes avec la table SQL.
     */
    protected $attributes = [
        'statut_terrain' => self::STATUT_DISPONIBLE,
    ];

    /**
     * ✅ Constantes pour les statuts de terrain
     */
    public const STATUT_DISPONIBLE   = 'disponible';
    public const STATUT_RESERVE      = 'reserve';
    public const STATUT_VENDU        = 'vendu';
    public const STATUT_INDISPONIBLE = 'indisponible';

    /**
     * Relations
     */

    // Un terrain peut être lié à plusieurs souscriptions
    public function souscriptions()
    {
        return $this->hasMany(Souscription::class, 'id_terrain', 'id_terrain');
    }

    /**
     * Accessors
     */

    // Exemple : Prix formaté
    public function getPrixFormatteAttribute()
    {
        return number_format($this->prix_unitaire, 0, ',', ' ') . ' FCFA';
    }

    // Vérifier si le terrain est disponible
    public function getEstDisponibleAttribute()
    {
        return $this->statut_terrain === self::STATUT_DISPONIBLE;
    }
}
