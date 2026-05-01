<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\Classes;

class ClassesController extends Controller
{
    public function index()
    {
        return response()->json(Classes::all());
    }

    public function store(StoreClassRequest $request)
    {
        $class = Classes::create($request->validated());

        return response()->json($class, 201);
    }

    public function show(Classes $class)
    {
        return response()->json($class);
    }

    public function update(UpdateClassRequest $request, Classes $class)
    {
        $class->update($request->validated());

        return response()->json($class->fresh());
    }

    public function destroy(Classes $class)
    {
        if ($class->students()->exists()) {
            return response()->json([
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar',
            ], 422);
        }

        $class->delete();

        return response()->json(['message' => 'Kelas berhasil dihapus']);
    }
}
