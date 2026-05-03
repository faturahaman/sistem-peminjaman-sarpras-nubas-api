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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            // Grade level: 10, 11, 12, or 13 (for specific majors like Analis Kimia)
            $table->unsignedTinyInteger('grade');

            // Major name stored as a plain string (e.g. PPLG, Farmasi, AK).
            // We intentionally avoid a separate majors table here because majors
            // don't carry extra attributes and can change freely each academic year.
            // If majors ever need their own data (head of dept, description, etc.),
            // extract them into a majors table and replace this with a foreign key.
            $table->string('major');

            // Class group number within the same grade+major (1, 2, 3, …)
            $table->unsignedTinyInteger('rombel');

            // Prevent duplicate combinations — a grade+major+rombel must be unique
            $table->unique(['grade', 'major', 'rombel']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
