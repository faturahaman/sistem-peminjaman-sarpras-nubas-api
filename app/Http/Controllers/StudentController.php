<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with('class')
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->get();

        return response()->json($students);
    }

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());

        return response()->json($student->load('class'), 201);
    }

    public function show(Student $student)
    {
        return response()->json($student->load('class'));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());

        return response()->json($student->fresh()->load('class'));
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json(['message' => 'Siswa berhasil dihapus']);
    }
}
