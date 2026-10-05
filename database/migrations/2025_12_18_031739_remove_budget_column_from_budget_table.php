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
        Schema::table('budget', function (Blueprint $table) {
            if (Schema::hasColumn('budget', 'budget')) {
                $table->dropColumn('budget');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget', function (Blueprint $table) {
            if (!Schema::hasColumn('budget', 'budget')) {
                $table->decimal('budget', 15, 2)->default(0)->after('deskripsi');
            }
        });
    }
};
