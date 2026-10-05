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
        // Create a minimal anggota table if it doesn't exist to satisfy legacy references
        if (!Schema::hasTable('anggota')) {
            Schema::create('anggota', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('anggota')) {
            Schema::dropIfExists('anggota');
        }
    }
};


