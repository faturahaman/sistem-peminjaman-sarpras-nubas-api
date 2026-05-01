<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes status enum from 'dipinjam'/'dikembalikan' to 'active'/'done'
     */
    public function up(): void
    {
        // Update existing data before altering the column
        DB::statement("UPDATE transactions SET status = 'active' WHERE status = 'dipinjam'");
        DB::statement("UPDATE transactions SET status = 'done' WHERE status = 'dikembalikan'");

        // Alter the enum column to use new values
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('active', 'done') NOT NULL");
    }

    /**
     * Reverse the migrations.
     * Reverts status enum from 'active'/'done' back to 'dipinjam'/'dikembalikan'
     */
    public function down(): void
    {
        // Temporarily allow old values by expanding the enum
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('active', 'done', 'dipinjam', 'dikembalikan') NOT NULL");

        // Revert existing data back to original values
        DB::statement("UPDATE transactions SET status = 'dipinjam' WHERE status = 'active'");
        DB::statement("UPDATE transactions SET status = 'dikembalikan' WHERE status = 'done'");

        // Restore the original enum definition
        DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('dipinjam', 'dikembalikan') NOT NULL");
    }
};
