<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evenement extends Model
{
    use HasFactory;

    protected $table = 'Evenement';
    protected $primaryKey = 'id_evenement';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_souscription',
        'id_type_evenement',
        'titre',
        'description',
        'date_debut_evenement',
        'date_fin_evenement',
        'date_prevue_fin',
        'lieu',
        'coordonnees_gps',
        'statut_evenement',
        'niveau_avancement_pourcentage',
        'etape_actuelle',
        'cout_estime',
        'cout_reel',
        'entreprise_responsable',
        'responsable_chantier',
        'priorite',
        'est_public',
        'notification_envoyee',
        'inscription_requise',
        'nombre_places_limite',
        'nombre_inscrits',
        'fichier_joint',
        'date_creation',
        'date_modification',
        'actif',
        'nombre_vues'
    ];

    protected $casts = [
        'date_debut_evenement' => 'date',
        'date_fin_evenement' => 'date',
        'date_prevue_fin' => 'date',
        'niveau_avancement_pourcentage' => 'integer',
        'cout_estime' => 'decimal:2',
        'cout_reel' => 'decimal:2',
        'est_public' => 'boolean',
        'notification_envoyee' => 'boolean',
        'inscription_requise' => 'boolean',
        'nombre_places_limite' => 'integer',
        'nombre_inscrits' => 'integer',
        'actif' => 'boolean',
        'nombre_vues' => 'integer'
    ];

    /**
     * Relations
     */
    public function typeEvenement()
    {
        return $this->belongsTo(TypeEvenement::class, 'id_type_evenement', 'id_type_evenement');
    }

    public function souscription()
    {
        return $this->belongsTo(Souscription::class, 'id_souscription', 'id_souscription');
    }

    // Relation vers tous les documents liés à cet événement
    public function documents()
    {
        return $this->hasMany(Document::class, 'id_source', 'id_evenement')
                    ->where('source_table', 'evenements');
    }

    /**
     * Accessors
     */
    public function getAvancementTexteAttribute()
    {
        return $this->niveau_avancement_pourcentage . '% complété';
    }
}
