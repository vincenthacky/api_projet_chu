<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatutReclamation extends Model
{
    use HasFactory;

    /**
     * Nom de la table
     */
    protected $table = 'StatutReclamation';

    /**
     * Clé primaire
     */
    protected $primaryKey = 'id_statut_reclamation';

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
    public $timestamps = false;

    /**
     * Champs autorisés pour assignation de masse
     */
    protected $fillable = [
        'libelle_statut_reclamation',
        'description_statut',
        'ordre_statut',
        'couleur_statut',
    ];

    /**
     * Valeurs par défaut
     */
    protected $attributes = [
        'ordre_statut'  => 0,
        'couleur_statut'=> '#6c757d',
    ];

    /**
     * Relations
     */

    // Une statut peut avoir plusieurs réclamations
    public function reclamations()
    {
        return $this->hasMany(Reclamation::class, 'id_statut_reclamation', 'id_statut_reclamation');
    }
}
