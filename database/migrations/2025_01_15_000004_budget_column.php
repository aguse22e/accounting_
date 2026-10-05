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
        if (Schema::hasTable('budget')) {
            Schema::table('budget', function (Blueprint $table) {
                // Add budget column if it doesn't exist
                if (!Schema::hasColumn('budget', 'budget')) {
                    $table->decimal('budget', 15, 2)->default(0)->after('deskripsi');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('budget')) {
            Schema::table('budget', function (Blueprint $table) {
                if (Schema::hasColumn('budget', 'budget')) {
                    $table->dropColumn('budget');
                }
            });
        }
    }
};
