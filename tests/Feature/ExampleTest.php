<?php

test('the login page is accessible', function () {
    $response = $this->get('/login');

    $response->assertOk();
});

test('the health endpoint is accessible', function () {
    $response = $this->get('/up');

    $response->assertOk();
});
