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
        Schema::table('users', function (Blueprint $table) {
            // Place columns after 'role' if it exists; otherwise, after 'password'
            $afterColumn = Schema::hasColumn('users', 'role') ? 'role' : 'password';
            $table->string('Kode_Budget', 25)->nullable()->after($afterColumn);
            $table->string('Kode_Project', 25)->nullable()->after('Kode_Budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns individually to support SQLite
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'Kode_Project')) {
                $table->dropColumn('Kode_Project');
            }
        });
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'Kode_Budget')) {
                $table->dropColumn('Kode_Budget');
            }
        });
    }
};
