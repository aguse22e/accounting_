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

        if (!Schema::hasTable('users')) {
            return;
        }

        // Drop foreign keys that reference users table
        $this->dropForeignKeys();

        Schema::table('users', function (Blueprint $table) {
            // Drop unnecessary columns if they exist
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

        // Rename id to Id_User and ensure INTEGER(9)
        if (Schema::hasColumn('users', 'id')) {
            try {
                DB::statement('ALTER TABLE users DROP PRIMARY KEY');
                DB::statement('ALTER TABLE users CHANGE COLUMN id Id_User INTEGER(9) AUTO_INCREMENT');
                DB::statement('ALTER TABLE users ADD PRIMARY KEY (Id_User)');
            } catch (\Exception $e) {
                // Column might already be renamed
            }
        }

        // Rename name to User_name and ensure VARCHAR(50)
        if (Schema::hasColumn('users', 'name')) {
            try {
                DB::statement('ALTER TABLE users CHANGE COLUMN name User_name VARCHAR(50) NOT NULL');
            } catch (\Exception $e) {
                // Column might already be renamed
            }
        }

        // Ensure email is VARCHAR(50)
        if (Schema::hasColumn('users', 'email')) {
            try {
                DB::statement('ALTER TABLE users MODIFY COLUMN email VARCHAR(50) NOT NULL');
            } catch (\Exception $e) {
                // Column might already be correct
            }
        }

        // Change role from integer/tinyInteger to VARCHAR(50)
        if (Schema::hasColumn('users', 'role')) {
            try {
                DB::statement('ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL');
            } catch (\Exception $e) {
                // Column might already be VARCHAR
            }
        }

        // Rename created_at to Create_at
        if (Schema::hasColumn('users', 'created_at')) {
            try {
                DB::statement('ALTER TABLE users CHANGE COLUMN created_at Create_at TIMESTAMP NULL DEFAULT NULL');
            } catch (\Exception $e) {
                // Column might already be renamed
            }
        }

        // Rename updated_at to Update_at
        if (Schema::hasColumn('users', 'updated_at')) {
            try {
                DB::statement('ALTER TABLE users CHANGE COLUMN updated_at Update_at TIMESTAMP NULL DEFAULT NULL');
            } catch (\Exception $e) {
                // Column might already be renamed
            }
        }

        // Re-add foreign keys with updated column names
        $this->addForeignKeys();
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

        if (!Schema::hasTable('users')) {
            return;
        }

        // Drop foreign keys
        $this->dropForeignKeys();

        // Revert column renames and types
        if (Schema::hasColumn('users', 'Update_at')) {
            try {
                DB::statement('ALTER TABLE users CHANGE COLUMN Update_at updated_at TIMESTAMP NULL DEFAULT NULL');
            } catch (\Exception $e) {}
        }

        if (Schema::hasColumn('users', 'Create_at')) {
            try {
                DB::statement('ALTER TABLE users CHANGE COLUMN Create_at created_at TIMESTAMP NULL DEFAULT NULL');
            } catch (\Exception $e) {}
        }

        if (Schema::hasColumn('users', 'role')) {
            try {
                DB::statement('ALTER TABLE users MODIFY COLUMN role TINYINT UNSIGNED DEFAULT 3');
            } catch (\Exception $e) {}
        }

        if (Schema::hasColumn('users', 'Id_User')) {
            try {
                DB::statement('ALTER TABLE users DROP PRIMARY KEY');
                DB::statement('ALTER TABLE users CHANGE COLUMN Id_User id BIGINT UNSIGNED AUTO_INCREMENT');
                DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
            } catch (\Exception $e) {}
        }

        if (Schema::hasColumn('users', 'User_name')) {
            try {
                DB::statement('ALTER TABLE users CHANGE COLUMN User_name name VARCHAR(255) NOT NULL');
            } catch (\Exception $e) {}
        }

        if (Schema::hasColumn('users', 'email')) {
            try {
                DB::statement('ALTER TABLE users MODIFY COLUMN email VARCHAR(255) NOT NULL');
            } catch (\Exception $e) {}
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

        // Re-add foreign keys with original column names
        $this->addForeignKeys();
    }

    /**
     * Drop foreign keys that reference users table
     */
    private function dropForeignKeys(): void
    {
        $tables = [
            'sessions' => ['user_id'],
            'transaksi_bank' => ['deleted_by'],
            'kode_akun' => ['deleted_by'],
            'activity_users' => ['user_id'],
            'user_activity' => ['id_user'],
            'detail_jurnal' => ['created_by', 'updated_by'],
            'jurnal' => ['created_by', 'updated_by'],
            'laporan_keuangan' => ['created_by', 'updated_by'],
        ];

        foreach ($tables as $table => $columns) {
            if (Schema::hasTable($table)) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        // Try to get constraint name from database
                        $constraintName = $this->getForeignKeyName($table, $column);
                        if ($constraintName) {
                            try {
                                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}");
                            } catch (\Exception $e) {
                                // Constraint might not exist
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Add foreign keys that reference users table
     */
    private function addForeignKeys(): void
    {
        $primaryKey = Schema::hasColumn('users', 'Id_User') ? 'Id_User' : 'id';
        
        $constraints = [
            'sessions' => ['user_id' => 'sessions_user_id_foreign'],
            'activity_users' => ['user_id' => 'activity_users_user_id_foreign'],
            'detail_jurnal' => ['created_by' => 'detail_jurnal_created_by_foreign', 'updated_by' => 'detail_jurnal_updated_by_foreign'],
            'jurnal' => ['created_by' => 'jurnal_created_by_foreign', 'updated_by' => 'jurnal_updated_by_foreign'],
        ];

        foreach ($constraints as $table => $columns) {
            if (Schema::hasTable($table)) {
                foreach ($columns as $column => $constraint) {
                    if (Schema::hasColumn($table, $column)) {
                        try {
                            $onDelete = (in_array($column, ['created_by', 'updated_by'])) ? 'ON DELETE SET NULL' : 'ON DELETE CASCADE';
                            $dataType = ($primaryKey === 'Id_User') ? 'INTEGER(9)' : 'BIGINT UNSIGNED';
                            DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} {$dataType}");
                            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES users({$primaryKey}) {$onDelete}");
                        } catch (\Exception $e) {
                            // Constraint might already exist
                        }
                    }
                }
            }
        }
    }

    /**
     * Get foreign key constraint name
     */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        try {
            $result = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $column]);

            if (!empty($result)) {
                return $result[0]->CONSTRAINT_NAME;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return null;
    }
};
