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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number')->unique(); // Jurnal-YYYYMMDD-XXXX
            $table->date('journal_date');
            $table->string('description');
            $table->string('reference')->nullable(); // No. Bukti
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->string('kode_project')->nullable(); // Project reference
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys will be added separately due to type compatibility issues
            // $table->foreign('created_by')->references('Id_User')->on('users')->onDelete('set null');
            // $table->foreign('posted_by')->references('Id_User')->on('users')->onDelete('set null');
            // $table->foreign('kode_project')->references('kode_project')->on('projects')->onDelete('set null');

            $table->index('journal_date');
            $table->index('status');
            $table->index('journal_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
