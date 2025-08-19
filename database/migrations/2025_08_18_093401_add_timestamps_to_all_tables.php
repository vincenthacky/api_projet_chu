<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
     /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Récupérer toutes les tables de la base de données actuelle
        $tables = DB::select('SHOW TABLES');
        $databaseName = env('DB_DATABASE');
        $key = 'Tables_in_' . $databaseName;

        foreach ($tables as $table) {
            $tableName = $table->$key;

            // Ajouter les colonnes uniquement si elles n'existent pas
            Schema::table($tableName, function ($tableBlueprint) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'created_at')) {
                    $tableBlueprint->timestamp('created_at')->nullable()->useCurrent();
                }
                if (!Schema::hasColumn($tableName, 'updated_at')) {
                    $tableBlueprint->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = env('DB_DATABASE');
        $key = 'Tables_in_' . $databaseName;

        foreach ($tables as $table) {
            $tableName = $table->$key;

            Schema::table($tableName, function ($tableBlueprint) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $tableBlueprint->dropColumn('created_at');
                }
                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $tableBlueprint->dropColumn('updated_at');
                }
            });
        }
    }
};
