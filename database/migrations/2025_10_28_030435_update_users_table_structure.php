<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip these modifications for SQLite (testing) as they're not supported
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        $this->dropForeignKeys();
        $this->modifyUsersId();
        $this->modifyForeignKeyColumns();
        DB::statement("ALTER TABLE users MODIFY COLUMN User_name VARCHAR(50)");
        DB::statement("ALTER TABLE users MODIFY COLUMN email VARCHAR(50)");
        $this->addForeignKeys();
    }

    public function down(): void
    {
        // Skip these modifications for SQLite (testing) as they're not supported
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        $this->dropForeignKeys();
        $this->modifyForeignKeyColumns(true);
        $this->modifyUsersId(true);
 
        DB::statement("ALTER TABLE users MODIFY COLUMN User_name VARCHAR(255)");
        DB::statement("ALTER TABLE users MODIFY COLUMN email VARCHAR(255)");
        $this->addForeignKeys();
    }
    
    private function dropForeignKeys(): void
    {
        $constraints = [
            'sessions' => ['sessions_user_id_foreign'],
            'transaksi_bank' => ['transaksi_bank_deleted_by_foreign'],
            'kode_akun' => ['kode_akun_deleted_by_foreign'],
            'activity_users' => ['activity_users_user_id_foreign'],
            'user_activity' => ['user_activity_id_user_foreign'],
            'detail_jurnal' => ['detail_jurnal_created_by_foreign', 'detail_jurnal_updated_by_foreign'],
            'jurnal' => ['jurnal_created_by_foreign', 'jurnal_updated_by_foreign'],
        ];
        
        foreach ($constraints as $table => $constraintList) {
            if (Schema::hasTable($table)) {
                foreach ($constraintList as $constraint) {
                    try {
                        DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
                    } catch (\Exception $e) {
                        // Constraint might not exist, continue
                    }
                }
            }
        }
    }
    
    private function modifyUsersId($revert = false): void
    {
        
        DB::statement('ALTER TABLE users MODIFY COLUMN id BIGINT UNSIGNED NOT NULL');
        
      
        DB::statement('ALTER TABLE users DROP PRIMARY KEY');
     
        if ($revert) {
            DB::statement('ALTER TABLE users MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT, ADD PRIMARY KEY (id)');
        } else {
            DB::statement('ALTER TABLE users MODIFY COLUMN id INTEGER(9) AUTO_INCREMENT, ADD PRIMARY KEY (id)');
        }
    }
    
    private function modifyForeignKeyColumns($revert = false): void
    {
        $columns = [
            'sessions' => ['user_id'],
            'transaksi_bank' => ['deleted_by'],
            'kode_akun' => ['deleted_by'],
            'activity_users' => ['user_id'],
            'user_activity' => ['id_user'],
            'detail_jurnal' => ['created_by', 'updated_by'],
            'jurnal' => ['created_by', 'updated_by'],
        ];
        
        foreach ($columns as $table => $columnList) {
            if (Schema::hasTable($table)) {
                foreach ($columnList as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        try {
                            if ($revert) {
                                DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} BIGINT UNSIGNED");
                            } else {
                                DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} INTEGER(9)");
                            }
                        } catch (\Exception $e) {
                      
                        }
                    }
                }
            }
        }
    }
    
    private function addForeignKeys(): void
    {
        // Re-create foreign keys
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
                            $onDelete = ($table === 'detail_jurnal' || $table === 'jurnal') ? 'ON DELETE SET NULL' : 'ON DELETE CASCADE';
                            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES users(id) {$onDelete}");
                        } catch (\Exception $e) {
                            // Constraint might already exist
                        }
                    }
                }
            }
        }
    }
};

