<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes:
     * - Rename `item_code` → `qr_code` (keep unique constraint)
     * - Drop `is_available` (boolean)
     * - Add `status` enum('available','borrowed') default 'available'
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Rename item_code to qr_code (unique constraint is preserved)
            $table->renameColumn('item_code', 'qr_code');

            // Drop the boolean is_available column
            $table->dropColumn('is_available');
        });

        // Add enum column using DB::statement for MySQL compatibility
        DB::statement("ALTER TABLE units ADD COLUMN status ENUM('available','borrowed') NOT NULL DEFAULT 'available' AFTER qr_code");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Drop the status enum column
            $table->dropColumn('status');

            // Rename qr_code back to item_code
            $table->renameColumn('qr_code', 'item_code');

            // Re-add the is_available boolean column with default true
            $table->boolean('is_available')->default(true);
        });
    }
};
