<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change projects.tanggal from DATE to TIMESTAMP to store both date and time
     */
    public function up(): void
    {
        echo "\n=== FIXING PROJECT TIMESTAMPS ===\n";
        
        if (Schema::hasTable('projects')) {
            // Backup data first
            DB::statement('CREATE TABLE IF NOT EXISTS projects_timestamp_backup AS SELECT * FROM projects');
            echo "✓ Backed up projects table\n";
            
            // Change column from DATE to TIMESTAMP
            Schema::table('projects', function (Blueprint $table) {
                // Change tanggal from DATE to TIMESTAMP to capture both date and time
                DB::statement('ALTER TABLE projects MODIFY COLUMN tanggal TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            });
            
            echo "✓ Changed projects.tanggal from DATE to TIMESTAMP\n";
            echo "✓ Now projects will store full datetime with hours and minutes\n";
        }
        
        echo "=== PROJECT TIMESTAMPS FIXED ===\n\n";
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                // Revert back to DATE
                DB::statement('ALTER TABLE projects MODIFY COLUMN tanggal DATE NULL');
            });
        }
    }
};
