<?php

it('renders the themed 404 page for missing routes', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound()
        ->assertSee('Page not found', false)
        ->assertSee('new-life-campus-logo.png', false)
        ->assertSee('Back to sign in', false);
});

it('renders the themed 503 maintenance page for pre-launch', function () {
    $html = view('errors.503')->render();

    expect($html)
        ->toContain('Almost here')
        ->toContain('Hang tight!')
        ->toContain('Something exciting is on the way')
        ->toContain('July 1, 2026')
        ->toContain('new-life-campus-logo.png')
        ->toContain('Try again');
});

it('renders a custom maintenance message when provided', function () {
    $html = view('errors.503', [
        'exception' => new Symfony\Component\HttpKernel\Exception\HttpException(503, 'Scheduled maintenance until 3 PM ET.'),
    ])->render();

    expect($html)
        ->toContain('503')
        ->toContain('We&#039;ll be right back')
        ->toContain('Scheduled maintenance until 3 PM ET.')
        ->not->toContain('July 1, 2026');
});
