<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNominalToTransaksiBankTable extends Migration
{
    public function up()
    {
        Schema::table('transaksi_bank', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_bank', 'nominal')) {
                $table->decimal('nominal', 13, 2)->nullable()->after('total');
            }
        });
    }

    public function down()
    {
        Schema::table('transaksi_bank', function (Blueprint $table) {
            $table->dropColumn('nominal');
        });
    }
}
