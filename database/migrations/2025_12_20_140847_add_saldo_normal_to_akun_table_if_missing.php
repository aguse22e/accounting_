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
        if (Schema::hasTable('akun') && !Schema::hasColumn('akun', 'saldo_normal')) {
            Schema::table('akun', function (Blueprint $table) {
                $table->enum('saldo_normal', ['debit', 'kredit'])->default('debit')->after('nama_akun');
            });
            
            // Update existing records to have default value if needed
            DB::table('akun')->whereNull('saldo_normal')->update(['saldo_normal' => 'debit']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('akun') && Schema::hasColumn('akun', 'saldo_normal')) {
            Schema::table('akun', function (Blueprint $table) {
                $table->dropColumn('saldo_normal');
            });
        }
    }
};
