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
        if (!Schema::hasTable('budget') || !Schema::hasColumn('budget', 'kode_budget')) {
            return;
        }

        // Check if kode_budget is already string type
        $columnType = DB::select("SHOW COLUMNS FROM budget WHERE Field = 'kode_budget'");
        if (!empty($columnType) && strpos($columnType[0]->Type, 'varchar') !== false) {
            // Already string, skip migration
            return;
        }

        // Drop primary key constraint first
        try {
            DB::statement('ALTER TABLE budget DROP PRIMARY KEY');
        } catch (\Exception $e) {
            // Primary key might not exist or already dropped
        }

        // Change column type from bigint to string
        DB::statement('ALTER TABLE budget MODIFY kode_budget VARCHAR(50) NOT NULL');

        // Re-add primary key
        try {
            DB::statement('ALTER TABLE budget ADD PRIMARY KEY (kode_budget)');
        } catch (\Exception $e) {
            // Primary key might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('budget') || !Schema::hasColumn('budget', 'kode_budget')) {
            return;
        }

        // Drop primary key
        try {
            DB::statement('ALTER TABLE budget DROP PRIMARY KEY');
        } catch (\Exception $e) {
            // Primary key might not exist
        }

        // Change back to bigint auto increment
        DB::statement('ALTER TABLE budget MODIFY kode_budget BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // Re-add primary key
        try {
            DB::statement('ALTER TABLE budget ADD PRIMARY KEY (kode_budget)');
        } catch (\Exception $e) {
            // Primary key might already exist
        }
    }
};
