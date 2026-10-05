<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure table and column exist first
        if (!Schema::hasTable('budget') || !Schema::hasColumn('budget', 'kode_project')) {
            return;
        }

        // Conditionally drop FK only if it exists to avoid 1091 errors
        $constraintName = 'budget_kode_project_foreign';
        $constraintExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'budget')
            ->where('COLUMN_NAME', 'kode_project')
            ->whereNotNull('CONSTRAINT_NAME')
            ->where('CONSTRAINT_NAME', $constraintName)
            ->exists();

        if ($constraintExists) {
            Schema::table('budget', function (Blueprint $table) use ($constraintName) {
                $table->dropForeign($constraintName);
            });
        }

        Schema::table('budget', function (Blueprint $table) {
            // Change column to string to match projects.kode_project
            $table->string('kode_project')->change();
        });

        // Recreate foreign key to projects.kode_project (if projects table/column exist)
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'kode_project')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->foreign('kode_project')->references('kode_project')->on('projects')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Skip for SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        Schema::table('budget', function (Blueprint $table) {
            try {
                $table->dropForeign(['kode_project']);
            } catch (\Throwable $e) {
            }

            // Revert back to unsignedBigInteger (original type in initial migration)
            $table->unsignedBigInteger('kode_project')->change();
            // Attempt to recreate original FK to project.Kode_project if needed
            try {
                $table->foreign('kode_project')->references('Kode_project')->on('project')->cascadeOnDelete();
            } catch (\Throwable $e) {
            }
        });
    }
};


