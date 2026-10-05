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
        if (!Schema::hasTable('budget')) {
            return;
        }

        // Check current column type
        $columnInfo = DB::select("SHOW COLUMNS FROM budget WHERE Field = 'kode_budget'");
        if (empty($columnInfo)) {
            return;
        }

        $currentType = $columnInfo[0]->Type;
        
        // If already varchar/string, skip
        if (stripos($currentType, 'varchar') !== false || stripos($currentType, 'char') !== false) {
            return;
        }

        // Drop primary key if exists
        try {
            DB::statement('ALTER TABLE budget DROP PRIMARY KEY');
        } catch (\Exception $e) {
            // Primary key might not exist
        }

        // Change column to string
        DB::statement("ALTER TABLE budget MODIFY kode_budget VARCHAR(50) NOT NULL");

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
        // Don't reverse - keep as string
    }
};
