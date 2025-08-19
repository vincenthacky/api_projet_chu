<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reclamation extends Model
{
    use HasFactory;

    /**
     * Nom de la table
     */
    protected $table = 'Reclamation';

    /**
     * Clé primaire
     */
    protected $primaryKey = 'id_reclamation';

    /**
     * Auto-increment
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire
     */
    protected $keyType = 'int';

    /**
     * Timestamps (la table utilise des champs timestamp personnalisés)
     */
    public $timestamps = false;

    /**
     * Champs autorisés pour assignation de masse
     */
    protected $fillable = [
        'id_souscription',
        'titre',
        'description',
        'type_reclamation',
        'date_reclamation',
        'id_statut_reclamation',
        'priorite',
        'reponse_admin',
        'date_reponse',
        'date_traitement',
        'date_resolution',
        'satisfaction_client',
    ];

    /**
     * Casting automatique
     */
    protected $casts = [
        'date_reclamation'   => 'datetime',
        'date_reponse'       => 'datetime',
        'date_traitement'    => 'datetime',
        'date_resolution'    => 'datetime',
    ];

    /**
     * Valeurs par défaut
     */
    protected $attributes = [
        'priorite' => 'normale',
    ];

    /**
     * Constantes pour type de réclamation
     */
    public const TYPE_ANOMALIE_PAIEMENT     = 'anomalie_paiement';
    public const TYPE_INFORMATION_ERRONEE   = 'information_erronee';
    public const TYPE_DOCUMENT_MANQUANT     = 'document_manquant';
    public const TYPE_AVANCEMENT_PROJET     = 'avancement_projet';
    public const TYPE_AUTRE                 = 'autre';

    /**
     * Constantes pour priorité
     */
    public const PRIORITE_BASSE   = 'basse';
    public const PRIORITE_NORMALE = 'normale';
    public const PRIORITE_HAUTE   = 'haute';
    public const PRIORITE_URGENTE = 'urgente';

    /**
     * Constantes pour satisfaction client
     */
    public const SAT_TRES_SATISFAIT = 'tres_satisfait';
    public const SAT_SATISFAIT      = 'satisfait';
    public const SAT_PEU_SATISFAIT  = 'peu_satisfait';
    public const SAT_INSATISFAIT    = 'insatisfait';

    /**
     * Relations
     */

    // Récupérer la souscription liée
    public function souscription()
    {
        return $this->belongsTo(Souscription::class, 'id_souscription', 'id_souscription');
    }

    // Récupérer le statut de la réclamation
    public function statut()
    {
        return $this->belongsTo(StatutReclamation::class, 'id_statut_reclamation', 'id_statut_reclamation');
    }

    /**
     * Accessors / Mutators
     */

    // Résumé de la réclamation
    public function getResumeAttribute()
    {
        return "{$this->titre} ({$this->type_reclamation}) - Priorité: {$this->priorite}";
    }

    // Vérifie si la réclamation est résolue
    public function getEstResoluAttribute()
    {
        return !is_null($this->date_resolution);
    }
}
