<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign keys referencing projects.kode_project
        $foreignKeys = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = 'projects'
            AND REFERENCED_COLUMN_NAME = 'kode_project'
            AND TABLE_SCHEMA = DATABASE()
        ");

        foreach ($foreignKeys as $fk) {
            try {
                Schema::table($fk->TABLE_NAME, function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project', 25)->change();
        });

        // Recreate foreign keys
        foreach ($foreignKeys as $fk) {
            if (Schema::hasTable($fk->TABLE_NAME) && Schema::hasColumn($fk->TABLE_NAME, 'kode_project')) {
                try {
                    Schema::table($fk->TABLE_NAME, function (Blueprint $table) {
                        $table->foreign('kode_project')->references('kode_project')->on('projects')->cascadeOnDelete();
                    });
                } catch (\Exception $e) {
                    // Skip if cannot recreate
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key
        try {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropForeign(['kode_project']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('kode_project', 50)->change(); // Revert to original varchar(50)
        });

        // Recreate original FK
        if (Schema::hasTable('budget') && Schema::hasColumn('budget', 'kode_project')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->foreign('kode_project')->references('kode_project')->on('projects')->cascadeOnDelete();
            });
        }
    }
};
