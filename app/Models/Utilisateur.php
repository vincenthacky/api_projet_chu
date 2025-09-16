<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Utilisateur extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Nom de la table (car ta table n'est pas au pluriel par défaut de Laravel).
     */
    protected $table = 'Utilisateur';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_utilisateur';

    /**
     * Indique si la clé est auto-incrémentée.
     */
    public $incrementing = true;

    /**
     * Type de la clé primaire.
     */
    protected $keyType = 'int';

    /**
     * Pas de created_at / updated_at par défaut dans ta table.
     */
    public $timestamps = true;

    /**
     * Champs remplissables.
     */
    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'email',
        'telephone',
        'poste',
        'service',
        'mot_de_passe',
        'date_inscription',
        'derniere_connexion',
        'est_administrateur',
        'statut_utilisateur',
        'token_reset',
        'token_expiration',
        'type'
    ];

    /**
     * Champs cachés (jamais retournés dans les JSON/API).
     */
    protected $hidden = [
        'mot_de_passe',
        'token_reset',
        'token_expiration',
    ];

      // Pour JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    




    /**
     * Casts automatiques.
     */
    protected $casts = [
        'date_inscription'   => 'datetime',
        'derniere_connexion' => 'datetime',
        'token_expiration'   => 'datetime',
        'est_administrateur' => 'boolean',
    ];

    /**
     * Redéfinition du mot de passe pour Laravel.
     * (Laravel utilise "password" comme champ par défaut, donc on mappe sur `mot_de_passe`).
     */
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    /**
     * ✅ Constantes de statut utilisateur
     */
    public const STATUT_ACTIF     = 'actif';
    public const STATUT_SUSPENDU  = 'suspendu';
    public const STATUT_INACTIF   = 'inactif';

    /**
     * Relations
     */

    // Souscriptions faites par l'utilisateur
    public function souscriptions()
    {
        return $this->hasMany(Souscription::class, 'id_utilisateur', 'id_utilisateur');
    }

    // Souscriptions gérées comme administrateur
    public function souscriptionsGerees()
    {
        return $this->hasMany(Souscription::class, 'id_admin', 'id_utilisateur');
    }

    /**
     * Accessors / Helpers
     */

    // Nom complet
    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    // Vérifie si l’utilisateur est admin
    public function getEstAdminAttribute()
    {
        return $this->est_administrateur === true;
    }

    // Vérifie si l’utilisateur est actif
    public function getEstActifAttribute()
    {
        return $this->statut_utilisateur === self::STATUT_ACTIF;
    }



    // 📂 Document spécifique : CNI
    public function cni()
    {
        return $this->hasOne(Document::class, 'id_source', 'id_utilisateur')
            ->where('source_table', 'utilisateurs')
            ->whereHas('typeDocument', fn($q) => $q->where('libelle_type_document', 'CNI'));
    }

    // 📂 Document spécifique : Carte Professionnelle
    public function carteProfessionnelle()
    {
        return $this->hasOne(Document::class, 'id_source', 'id_utilisateur')
            ->where('source_table', 'utilisateurs')
            ->whereHas('typeDocument', fn($q) => $q->where('libelle_type_document', 'Carte Professionnelle'));
    }

    // 📂 Document spécifique : Fiche de Souscription
    public function ficheSouscription()
    {
        return $this->hasOne(Document::class, 'id_source', 'id_utilisateur')
            ->where('source_table', 'utilisateurs')
            ->whereHas('typeDocument', fn($q) => $q->where('libelle_type_document', 'Fiche de Souscription'));
    }

    // 📂 Document spécifique : Photo de Profil
    public function photoProfil()
    {
        return $this->hasOne(Document::class, 'id_source', 'id_utilisateur')
            ->where('source_table', 'utilisateurs')
            ->whereHas('typeDocument', fn($q) => $q->where('libelle_type_document', 'Photo de Profil'));
    }
}
