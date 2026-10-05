<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDebitKreditToTransaksiBankTable extends Migration
{
    public function up()
    {
        Schema::table('transaksi_bank', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_bank', 'debit')) {
                $table->decimal('debit', 13, 2)->nullable()->after('nominal');
            }
            if (!Schema::hasColumn('transaksi_bank', 'kredit')) {
                $table->decimal('kredit', 13, 2)->nullable()->after('debit');
            }
        });
    }

    public function down()
    {
        Schema::table('transaksi_bank', function (Blueprint $table) {
            $table->dropColumn(['debit', 'kredit']);
        });
    }
}
