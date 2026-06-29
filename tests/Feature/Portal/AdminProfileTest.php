<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shows the admin profile page', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->get(route('admin.profile'))
        ->assertOk();
});

it('updates the admin name', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->put(route('admin.profile.update'), ['name' => 'New Admin Name'])
        ->assertRedirect(route('admin.profile'))
        ->assertSessionHas('status');

    expect($admin->fresh()->name)->toBe('New Admin Name');
});

it('requires a name when updating the admin profile', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->put(route('admin.profile.update'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('uploads and replaces an admin avatar then removes it', function () {
    Storage::fake('public');
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ])
        ->assertRedirect(route('admin.profile'));

    $first = $admin->fresh()->avatar_path;
    expect($first)->not->toBeNull();
    Storage::disk('public')->assertExists($first);

    $this->actingAs($admin)
        ->post(route('admin.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('me2.png'),
        ])
        ->assertRedirect(route('admin.profile'));

    Storage::disk('public')->assertMissing($first);

    $this->actingAs($admin)
        ->delete(route('admin.profile.avatar.destroy'))
        ->assertRedirect(route('admin.profile'));

    expect($admin->fresh()->avatar_path)->toBeNull();
});

it('rejects a non-image avatar upload', function () {
    Storage::fake('public');
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');
});
