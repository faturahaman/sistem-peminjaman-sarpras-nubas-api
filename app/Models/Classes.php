<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = ['grade', 'major', 'rombel'];

    protected $casts = [
        'grade'  => 'integer',
        'rombel' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Accessor
    // -------------------------------------------------------------------------

    /**
     * Returns a human-readable class label, e.g. "10 PPLG 1", "11 Farmasi 2".
     * Accessible as $class->full_name (Laravel auto-resolves getXxxAttribute).
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->grade} {$this->major} {$this->rombel}";
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * A class has many students.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // -------------------------------------------------------------------------
    // Scopes — reusable query helpers
    // -------------------------------------------------------------------------

    /**
     * Filter classes by major.
     *
     * Usage: Classes::byMajor('PPLG')->get();
     */
    public function scopeByMajor($query, string $major)
    {
        return $query->where('major', $major);
    }

    /**
     * Filter classes by grade.
     *
     * Usage: Classes::byGrade(10)->get();
     */
    public function scopeByGrade($query, int $grade)
    {
        return $query->where('grade', $grade);
    }
}
