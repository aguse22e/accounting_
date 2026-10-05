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
        Schema::create('general_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('journal_line_id')->nullable()->constrained()->onDelete('set null');
            $table->string('account_code'); // Kode akun
            $table->date('posting_date');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0); // Running balance per account
            $table->text('description')->nullable();
            $table->string('reference')->nullable(); // Journal number
            $table->string('kode_project')->nullable(); // Project reference
            $table->timestamps();

            // Foreign keys commented out due to compatibility issues
            // $table->foreign('account_code')->references('kode_akun')->on('akun')->onDelete('restrict');
            // $table->foreign('kode_project')->references('kode_project')->on('projects')->onDelete('set null');

            $table->index('account_code');
            $table->index('posting_date');
            $table->index('journal_entry_id');
            $table->index(['account_code', 'posting_date']); // Composite index for queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledger');
    }
};
