<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('activity_users', 'users')) {
            Schema::table('activity_users', function (Blueprint $table) {
                $table->string('users')->nullable()->after('new_data');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_users', 'users')) {
            Schema::table('activity_users', function (Blueprint $table) {
                $table->dropColumn('users');
            });
        }
    }
};


