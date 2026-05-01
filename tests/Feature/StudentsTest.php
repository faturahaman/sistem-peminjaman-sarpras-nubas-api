<?php

use App\Models\Classes;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns list of students with class info', function () {
    Student::factory()->count(3)->create();
    $this->getJson('/api/students')->assertOk()->assertJsonCount(3);
});

it('filters students by class_id', function () {
    $class1 = Classes::factory()->create();
    $class2 = Classes::factory()->create();
    Student::factory()->count(2)->create(['class_id' => $class1->id]);
    Student::factory()->count(3)->create(['class_id' => $class2->id]);

    $response = $this->getJson("/api/students?class_id={$class1->id}")->assertOk();
    expect($response->json())->toHaveCount(2);
    collect($response->json())->each(fn ($s) => expect($s['class_id'])->toBe($class1->id));
});

it('rejects duplicate NIS', function () {
    $class = Classes::factory()->create();
    Student::factory()->create(['nis' => '1234567890', 'class_id' => $class->id]);
    $this->postJson('/api/students', ['name' => 'Test', 'nis' => '1234567890', 'class_id' => $class->id])
        ->assertStatus(422);
});
