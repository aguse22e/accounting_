<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('budget')) { return; }

        // Drop columns individually to support SQLite
        if (Schema::hasColumn('budget', 'actual')) {
            Schema::table('budget', function (Blueprint $table) {
                $table->dropColumn('actual');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('budget')) { return; }
        Schema::table('budget', function (Blueprint $table) {
            if (!Schema::hasColumn('budget', 'actual')) {
                $table->decimal('actual', 15, 2)->default(0)->after('budget');
            }
        });
    }
};


