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
        if (!Schema::hasTable('budget') || !Schema::hasColumn('budget', 'kode_project')) {
            return;
        }

        // Drop foreign key constraint if exists
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'budget' 
                AND COLUMN_NAME = 'kode_project' 
                AND CONSTRAINT_NAME != 'PRIMARY'
            ");
            
            foreach ($foreignKeys as $fk) {
                Schema::table('budget', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }
        } catch (\Exception $e) {
            // Ignore if foreign key doesn't exist
        }

        // Change column type to string
        Schema::table('budget', function (Blueprint $table) {
            $table->string('kode_project', 50)->change();
        });

        // Recreate foreign key to project table if it exists
        if (Schema::hasTable('project') && Schema::hasColumn('project', 'Kode_project')) {
            try {
                Schema::table('budget', function (Blueprint $table) {
                    $table->foreign('kode_project')->references('Kode_project')->on('project')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Ignore if foreign key creation fails
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('budget') || !Schema::hasColumn('budget', 'kode_project')) {
            return;
        }

        // Drop foreign key if exists
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'budget' 
                AND COLUMN_NAME = 'kode_project' 
                AND CONSTRAINT_NAME != 'PRIMARY'
            ");
            
            foreach ($foreignKeys as $fk) {
                Schema::table('budget', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Revert to unsignedBigInteger (original type)
        Schema::table('budget', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_project')->change();
        });
    }
};
