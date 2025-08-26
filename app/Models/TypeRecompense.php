<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeRecompense extends Model
{
    use HasFactory;

    protected $table = 'TypeRecompense';
    protected $primaryKey = 'id_type_recompense';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'libelle_type_recompense',
        'description_type',
        'valeur_monetaire',
        'est_monetaire',
        'conditions_attribution',
    ];

    protected $casts = [
        'valeur_monetaire' => 'decimal:2',
        'est_monetaire'    => 'boolean',
    ];

    /**
     * Relations
     */

    // Toutes les récompenses de ce type
    public function recompenses()
    {
        return $this->hasMany(Recompense::class, 'id_type_recompense', 'id_type_recompense');
    }

    /**
     * Helpers
     */

    public function getResumeAttribute(): string
    {
        $type = $this->est_monetaire ? "Monétaire" : "Non-monétaire";
        return "{$this->libelle_type_recompense} ({$type})";
    }
}
