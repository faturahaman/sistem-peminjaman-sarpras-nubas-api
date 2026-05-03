<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\Classes;
use Illuminate\Http\Request;

class ClassesController extends Controller
{
    /**
     * List all classes.
     * Optional query params: ?major=PPLG  or  ?grade=10
     */
    public function index(Request $request)
    {
        $query = Classes::query()->withCount('students');

        if ($request->filled('major')) {
            $query->byMajor($request->major);
        }

        if ($request->filled('grade')) {
            $query->byGrade((int) $request->grade);
        }

        $classes = $query->orderBy('grade')->orderBy('major')->orderBy('rombel')->get();

        // Append the full_name accessor to every item in the collection
        return response()->json($classes->append('full_name'));
    }

    public function store(StoreClassRequest $request)
    {
        $class = Classes::create($request->validated());

        return response()->json($class->append('full_name'), 201);
    }

    public function show(Classes $class)
    {
        return response()->json(
            $class->load('students')->append('full_name')
        );
    }

    public function update(UpdateClassRequest $request, Classes $class)
    {
        $class->update($request->validated());

        return response()->json($class->fresh()->append('full_name'));
    }

    public function destroy(Classes $class)
    {
        if ($class->students()->exists()) {
            return response()->json([
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar.',
            ], 422);
        }

        $class->delete();

        return response()->json(['message' => 'Kelas berhasil dihapus.']);
    }
}
