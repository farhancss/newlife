<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads a student avatar and returns json when requested', function () {
    Storage::fake('public');
    [$user] = completeStudent();

    $response = $this->actingAs($user)
        ->postJson(route('student.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ]);

    $response->assertOk()->assertJsonStructure(['message', 'avatar_url']);
    expect($user->fresh()->avatar_path)->not->toBeNull();
});

it('removes a student avatar via a redirect request', function () {
    Storage::fake('public');
    [$user] = completeStudent();

    $this->actingAs($user)
        ->post(route('student.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('me.png'),
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->delete(route('student.profile.avatar.destroy'))
        ->assertRedirect();

    expect($user->fresh()->avatar_path)->toBeNull();
});

it('saves a profile section change', function () {
    [$user] = completeStudent();

    $this->actingAs($user)
        ->post(route('student.profile.update'), [
            'section' => 1,
            'action' => 'save',
            'first_name' => 'Renamed',
            'last_name' => 'Student',
            'phone' => '757-555-0100',
            'school' => 'ODU',
            'incoming_year' => '2026',
        ])
        ->assertRedirect();

    expect($user->studentProfile->fresh()->first_name)->toBe('Renamed');
});
