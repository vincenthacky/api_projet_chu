<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recompense extends Model
{
    use HasFactory;

    protected $table = 'Recompense';
    protected $primaryKey = 'id_recompense';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // pas de created_at / updated_at dans ta table

    protected $fillable = [
        'id_souscription',
        'id_type_recompense',
        'description',
        'motif_recompense',
        'periode_merite',
        //'valeur_recompense',
        'statut_recompense',
        'date_attribution',
        'date_attribution_effective',
        'commentaire_admin',
    ];

    protected $casts = [
       
        'date_attribution'          => 'datetime',
        'date_attribution_effective'=> 'datetime',
    ];

    /**
     * ✅ Statuts possibles
     */
    public const STATUT_DUE       = 'due';
    public const STATUT_ATTRIBUEE = 'attribuee';
    public const STATUT_ANNULEE   = 'annulee';

    protected $attributes = [
        'statut_recompense' => self::STATUT_DUE,
    ];

    /**
     * Relations
     */

    // Lien vers la souscription
    public function souscription()
    {
        return $this->belongsTo(Souscription::class, 'id_souscription', 'id_souscription')->orderBy('created_at', 'desc');
    }

    // Lien vers le type de récompense
    public function typeRecompense()
    {
        return $this->belongsTo(TypeRecompense::class, 'id_type_recompense', 'id_type_recompense');
    }

    /**
     * Accessors / Helpers
     */

    public function getEstAttribueeAttribute(): bool
    {
        return $this->statut_recompense === self::STATUT_ATTRIBUEE;
    }

    public function getEstDueAttribute(): bool
    {
        return $this->statut_recompense === self::STATUT_DUE;
    }

    public function getEstAnnuleeAttribute(): bool
    {
        return $this->statut_recompense === self::STATUT_ANNULEE;
    }

    // Texte lisible
    public function getResumeAttribute(): string
    {
        return "Récompense #{$this->id_recompense} ({$this->statut_recompense}) - {$this->valeur_recompense} FCFA";
    }
}
