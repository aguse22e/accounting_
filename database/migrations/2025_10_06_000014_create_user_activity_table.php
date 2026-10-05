<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserActivityTable extends Migration
{
    public function up()
    {
        Schema::create('user_activity', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_user');
            $table->enum('jenis_transaksi', ['Kas', 'Bank', 'Memorial']);
            $table->enum('tipe', ['Insert', 'Update', 'Delete']);
            $table->text('keterangan');
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_activity');
    }
}
