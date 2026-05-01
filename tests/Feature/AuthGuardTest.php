<?php

test('dashboard redirects guests to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('api routes redirect guests to login', function () {
    $response = $this->get('/api/getunit');

    $response->assertRedirect('/login');
});
