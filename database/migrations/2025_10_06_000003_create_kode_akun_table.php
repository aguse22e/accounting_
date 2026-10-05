<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKodeAkunTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('kode_akun')) {
            return;
        }
        Schema::create('kode_akun', function (Blueprint $table) {
            $table->string('kode_akun', 15)->primary();
            $table->string('nama', 40);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->enum('tipe', ['Debit', 'Kredit'])->nullable();

            // Keep FK to users only if table exists.
            if (Schema::hasTable('users')) {
                $table->foreign('deleted_by')->references('id')->on('users');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('kode_akun');
    }
}
