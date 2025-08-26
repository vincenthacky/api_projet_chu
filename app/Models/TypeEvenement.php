<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeEvenement extends Model
{
    use HasFactory;

    protected $table = 'TypeEvenement';
    protected $primaryKey = 'id_type_evenement';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // si tu n'as pas created_at / updated_at

    protected $fillable = [
        'libelle_type_evenement',
        'description_type',
        'categorie_type',
        'couleur_affichage',
        'icone_type',
        'ordre_affichage',
    ];

    /**
     * Relations
     */
    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'id_type_evenement', 'id_type_evenement');
    }
}
