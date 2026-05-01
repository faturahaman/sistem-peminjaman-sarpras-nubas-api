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
        Schema::table('units', function (Blueprint $table) {
            $table->index('status', 'units_status_index');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->index(['transaction_id', 'unit_id'], 'transaction_details_transaction_id_unit_id_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['student_id', 'status'], 'transactions_student_id_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('units_status_index');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex('transaction_details_transaction_id_unit_id_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_student_id_status_index');
        });
    }
};
