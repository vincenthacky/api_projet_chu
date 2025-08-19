<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Utilisateur', function (Blueprint $table) {
            $table->string('type')->after('poste')->default('default')->comment('Rôle de l’utilisateur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Utilisateur', function (Blueprint $table) {
             $table->dropColumn('type');
        });
    }
};
