<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanPaiement extends Model
{
    use HasFactory;

    /**
     * Nom de la table
     */
    protected $table = 'PlanPaiement';

    /**
     * Clé primaire
     */
    protected $primaryKey = 'id_plan_paiement';

    /**
     * Auto-increment
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire
     */
    protected $keyType = 'int';

    /**
     * Timestamps
     */
    public $timestamps = false; // La table utilise date_saisie mais pas created_at/updated_at classiques

    /**
     * Champs autorisés pour assignation de masse
     */
    protected $fillable = [
        'id_souscription',
        'numero_mensualite',
        'montant_versement_prevu',
        'date_limite_versement',
        'date_paiement_effectif',
        'montant_paye',
        'mode_paiement',
        'reference_paiement',
        'est_paye',
        'penalite_appliquee',
        'statut_versement',
        'commentaire_paiement',
        'date_saisie',
    ];

    /**
     * Casting automatique
     */
    protected $casts = [
        'date_limite_versement'     => 'date',
        'date_paiement_effectif'    => 'date',
        'montant_versement_prevu'   => 'decimal:2',
        'montant_paye'              => 'decimal:2',
        'penalite_appliquee'        => 'decimal:2',
        'est_paye'                  => 'boolean',
        'date_saisie'               => 'datetime',
    ];

    /**
     * Constantes pour le mode de paiement
     */
    public const MODE_ESPECES       = 'especes';
    public const MODE_VIREMENT      = 'virement';
    public const MODE_MOBILE_MONEY  = 'mobile_money';
    public const MODE_CHEQUE        = 'cheques';

    /**
     * Constantes pour le statut de versement
     */
    public const STATUT_EN_ATTENTE    = 'en_attente';
    public const STATUT_PAYE_A_TEMPS  = 'paye_a_temps';
    public const STATUT_PAYE_EN_RETARD= 'paye_en_retard';
    public const STATUT_NON_PAYE      = 'non_paye';

    /**
     * Relations
     */

    // La souscription liée
    public function souscription()
    {
        return $this->belongsTo(Souscription::class, 'id_souscription', 'id_souscription');
    }

    /**
     * Accessors / Mutators
     */

    // Vérifie si le paiement est effectué
    public function getEstPayeAttribute($value)
    {
        return (bool) $value;
    }

    // Résumé du paiement
    public function getResumeAttribute()
    {
        return "Mensualité #{$this->numero_mensualite} - Prévu: {$this->montant_versement_prevu} FCFA - Payé: {$this->montant_paye} FCFA";
    }

    
}
