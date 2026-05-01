<?php

use App\Models\Classes;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns list of classes', function () {
    Classes::factory()->count(3)->create();
    $this->getJson('/api/classes')->assertOk()->assertJsonCount(3);
});

it('creates a new class', function () {
    $this->postJson('/api/classes', ['class' => '10', 'major' => 'RPL'])
        ->assertCreated()
        ->assertJsonPath('class', '10');
});

it('cannot delete class with students', function () {
    $class = Classes::factory()->create();
    Student::factory()->create(['class_id' => $class->id]);
    $this->deleteJson("/api/classes/{$class->id}")->assertStatus(422);
});

it('deletes class without students', function () {
    $class = Classes::factory()->create();
    $this->deleteJson("/api/classes/{$class->id}")->assertOk();
    $this->assertDatabaseMissing('classes', ['id' => $class->id]);
});
