<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Souscription extends Model
{
    use HasFactory;

    /**
     * Nom de la table (optionnel si le nom suit la convention Laravel).
     */
    protected $table = 'Souscription';

    /**
     * Clé primaire de la table.
     */
    protected $primaryKey = 'id_souscription';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire.
     */
    protected $keyType = 'int';

    /**
     * Pas de created_at / updated_at (car ta table utilise date_souscription).
     */
    public $timestamps = true;

    /**
     * Champs autorisés pour l’assignation de masse.
     */
    protected $fillable = [
        'id_utilisateur',
        'id_terrain',
        'id_admin',
        'date_souscription',
        'nombre_terrains',
        'montant_mensuel',
        'nombre_mensualites',
        'date_debut_paiement',
        'statut_souscription',
        'groupe_souscription',
        'notes_admin',
    ];

    /**
     * Casting automatique des colonnes.
     */
    protected $casts = [
        'date_souscription'      => 'datetime',
        'date_debut_paiement'    => 'date',
        'date_fin_prevue'        => 'date',
        'montant_mensuel'        => 'decimal:2',
        'montant_total_souscrit' => 'decimal:2',
    ];

    /**
     * Valeurs par défaut cohérentes avec ta migration SQL.
     */
    protected $attributes = [
        'nombre_terrains'     => 1,
        'montant_mensuel'     => 64400.00,
        'nombre_mensualites'  => 64,
        'statut_souscription' => self::STATUT_ACTIVE,
        'date_debut_paiement' => '2024-05-01',
    ];

    /**
     * ✅ Constantes pour les statuts
     */
    public const STATUT_ACTIVE    = 'active';
    public const STATUT_SUSPENDUE = 'suspendue';
    public const STATUT_TERMINEE  = 'terminee';
    public const STATUT_RESILLEE  = 'resillee';

    /**
     * Relations
     */

    // Souscripteur (lié à Utilisateur)
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    // Terrain principal lié
    public function terrain()
    {
        return $this->belongsTo(Terrain::class, 'id_terrain', 'id_terrain');
    }

    // Admin gestionnaire (aussi lié à Utilisateur mais flag est_administrateur = 1)
    public function admin()
    {
        return $this->belongsTo(Utilisateur::class, 'id_admin', 'id_utilisateur');
    }

    /**
     * Accessors / Mutators
     */

    // Résumé textuel de la souscription
    public function getResumeAttribute()
    {
        return "Souscription #{$this->id_souscription} - {$this->nombre_terrains} terrain(s) - {$this->montant_total_souscrit} FCFA";
    }

    // Vérifier si la souscription est encore active
    public function getEstActiveAttribute()
    {
        return $this->statut_souscription === self::STATUT_ACTIVE;
    }

     public function planpaiements()
    {
        return $this->hasMany(PlanPaiement::class, 'id_souscription', 'id_souscription');
    }
}
