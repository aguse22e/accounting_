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
        if (Schema::hasColumn('activity_users', 'user_agent')) {
            Schema::table('activity_users', function (Blueprint $table) {
                $table->renameColumn('user_agent', 'users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('activity_users', 'users')) {
            Schema::table('activity_users', function (Blueprint $table) {
                $table->renameColumn('users', 'user_agent');
            });
        }
    }
};
