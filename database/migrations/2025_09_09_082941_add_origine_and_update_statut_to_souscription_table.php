<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter les migrations.
     */
    public function up(): void
    {
        Schema::table('Souscription', function (Blueprint $table) {
            // ⚠️ si statut_souscription existe déjà, on fait un modify (selon ta DB),
            // sinon on ajoute la colonne
            if (!Schema::hasColumn('Souscription', 'origine')) {
                $table->enum('origine', ['admin', 'utilisateur'])
                      ->default('admin')
                      ->after('id_admin');
            }

            if (!Schema::hasColumn('Souscription', 'statut_souscription')) {
                $table->enum('statut_souscription', [
                        'en_attente',
                        'validee',
                        'rejete',
                        'active',
                        'terminee',
                        'resillee'
                    ])
                    ->default('validee')
                    ->after('origine');
            } else {
                // Si la colonne existe déjà mais doit avoir une nouvelle valeur par défaut
                $table->enum('statut_souscription', [
                        'en_attente',
                        'validee',
                        'rejete',
                        'active',
                        'terminee',
                        'resillee'
                    ])
                    ->default('validee')
                    ->change();
            }
        });
    }

    /**
     * Annuler les migrations.
     */
    public function down(): void
    {
        Schema::table('Souscription', function (Blueprint $table) {
            if (Schema::hasColumn('Souscription', 'origine')) {
                $table->dropColumn('origine');
            }
            // ⚠️ On ne supprime pas statut_souscription car elle existait peut-être déjà
        });
    }
};
