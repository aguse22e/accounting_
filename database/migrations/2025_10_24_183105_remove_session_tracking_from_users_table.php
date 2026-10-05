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
        // Drop columns individually to support SQLite
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_session_id')) {
                $table->dropColumn('current_session_id');
            }
        });
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_ip')) {
                $table->dropColumn('last_login_ip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('current_session_id')->nullable()->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('current_session_id');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
        });
    }
};
