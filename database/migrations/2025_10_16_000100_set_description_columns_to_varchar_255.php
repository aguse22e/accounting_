<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $databaseName = DB::getDatabaseName();
        // Candidate column names considered as description fields
        $candidateColumns = ['deskripsi','description','keterangan','catatan','remarks','uraian'];

        // Fetch existing matching columns in current schema
        $placeholders = implode(',', array_fill(0, count($candidateColumns), '?'));
        $columns = DB::select(
            "SELECT TABLE_NAME as table_name, COLUMN_NAME as column_name, IS_NULLABLE as is_nullable, COLUMN_TYPE as column_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME IN ($placeholders)",
            array_merge([$databaseName], $candidateColumns)
        );

        foreach ($columns as $col) {
            $table = $col->table_name;
            $column = $col->column_name;
            $nullable = strtoupper($col->is_nullable ?? 'YES') === 'YES' ? 'NULL' : 'NOT NULL';

            // Convert to VARCHAR(255) while preserving nullability
            // Note: Using raw SQL avoids requiring doctrine/dbal for column modification
            $sql = sprintf('ALTER TABLE `%s` MODIFY `%s` VARCHAR(255) %s', $table, $column, $nullable);
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Attempt a compatible fallback (some MySQL variants require USING/CONVERT)
                try {
                    DB::statement(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` VARCHAR(255) %s', $table, $column, $column, $nullable));
                } catch (\Throwable $e2) {
                    // Log and continue; do not hard-fail the whole migration
                    logger()->warning('Column alter to VARCHAR(255) failed', [
                        'table' => $table,
                        'column' => $column,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $databaseName = DB::getDatabaseName();
        $candidateColumns = ['deskripsi','description','keterangan','catatan','remarks','uraian'];
        $placeholders = implode(',', array_fill(0, count($candidateColumns), '?'));
        $columns = DB::select(
            "SELECT TABLE_NAME as table_name, COLUMN_NAME as column_name, IS_NULLABLE as is_nullable
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME IN ($placeholders)",
            array_merge([$databaseName], $candidateColumns)
        );

        foreach ($columns as $col) {
            $table = $col->table_name;
            $column = $col->column_name;
            $nullable = strtoupper($col->is_nullable ?? 'YES') === 'YES' ? 'NULL' : 'NOT NULL';

            // Revert to TEXT to be conservative
            $sql = sprintf('ALTER TABLE `%s` MODIFY `%s` TEXT %s', $table, $column, $nullable);
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                try {
                    DB::statement(sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` TEXT %s', $table, $column, $column, $nullable));
                } catch (\Throwable $e2) {
                    logger()->warning('Column revert to TEXT failed', [
                        'table' => $table,
                        'column' => $column,
                        'error' => $e2->getMessage(),
                    ]);
                }
            }
        }
    }
};


