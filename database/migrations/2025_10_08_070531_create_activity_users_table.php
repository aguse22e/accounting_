<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_users', function (Blueprint $table) {
            $table->id();
            
            // 1. DEFINISIKAN KOLOM DULU
            $table->unsignedInteger('user_id');
            $table->string('action', 25);
            $table->string('model_name', 25);
            $table->string('model_id', 255);
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('users')->nullable(); // HAPUS INI jika tidak perlu
            
            $table->timestamps();
            
            // 2. BARU BUAT FOREIGN KEY & INDEX
            $table->foreign('user_id')->references('Id_User')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index(['model_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_users');
    }
};
