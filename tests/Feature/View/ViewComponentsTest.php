<?php

use App\Models\User;

it('renders the avatar component', function () {
    expect((string) $this->blade('<x-ui.avatar />'))->not->toBe('');
});

it('renders the preloader component', function () {
    expect((string) $this->blade('<x-common.preloader />'))->not->toBe('');
});

it('renders the user dropdown for a guest with portal fallbacks', function () {
    $html = (string) $this->blade('<x-header.user-dropdown />');

    expect($html)->toContain('Student User');
});

it('renders the user dropdown for an authenticated user', function () {
    $user = User::factory()->create(['name' => 'Jordan Rivers']);

    $this->actingAs($user);

    $html = (string) $this->blade('<x-header.user-dropdown />');

    expect($html)->toContain('Jordan Rivers');
});
