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
        // Skip modifications for SQLite (testing) as they're not fully supported
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Drop unnecessary columns
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
            if (Schema::hasColumn('users', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });

        // Use raw SQL for complex column modifications
        // Rename id to Id_User and ensure INTEGER(9)
        if (Schema::hasColumn('users', 'id')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN id Id_User INTEGER(9) AUTO_INCREMENT');
            // Primary key should be maintained
        } elseif (Schema::hasColumn('users', 'Id_User')) {
            DB::statement('ALTER TABLE users MODIFY COLUMN Id_User INTEGER(9) AUTO_INCREMENT');
        }

        // Rename name to User_name and ensure VARCHAR(50)
        if (Schema::hasColumn('users', 'name')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN name User_name VARCHAR(50) NOT NULL');
        }

        // Ensure email is VARCHAR(50) and UNIQUE
        if (Schema::hasColumn('users', 'email')) {
            DB::statement('ALTER TABLE users MODIFY COLUMN email VARCHAR(50) NOT NULL');
        }

        // Ensure password is VARCHAR(255)
        if (Schema::hasColumn('users', 'password')) {
            DB::statement('ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL');
        }

        // Change role from unsignedTinyInteger to VARCHAR(50) and rename to Role
        if (Schema::hasColumn('users', 'role')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN role Role VARCHAR(50) NOT NULL');
        }

        // Rename created_at to Create_at
        if (Schema::hasColumn('users', 'created_at')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN created_at Create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }

        // Rename updated_at to Update_at
        if (Schema::hasColumn('users', 'updated_at')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN updated_at Update_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip modifications for SQLite (testing) as they're not fully supported
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Revert column renames and types
        if (Schema::hasColumn('users', 'Update_at')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN Update_at updated_at TIMESTAMP NULL DEFAULT NULL');
        }

        if (Schema::hasColumn('users', 'Create_at')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN Create_at created_at TIMESTAMP NULL DEFAULT NULL');
        }

        if (Schema::hasColumn('users', 'Role')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN Role role TINYINT UNSIGNED DEFAULT 3');
        }

        if (Schema::hasColumn('users', 'Id_User')) {
            DB::statement('ALTER TABLE users DROP PRIMARY KEY');
            DB::statement('ALTER TABLE users CHANGE COLUMN Id_User id BIGINT UNSIGNED AUTO_INCREMENT');
            DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
        }

        if (Schema::hasColumn('users', 'User_name')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN User_name name VARCHAR(255) NOT NULL');
        }

        if (Schema::hasColumn('users', 'email')) {
            DB::statement('ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NOT NULL');
        }

        // Re-add dropped columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
            if (!Schema::hasColumn('users', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('role');
            }
        });
    }
};
