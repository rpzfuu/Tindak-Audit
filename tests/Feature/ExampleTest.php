<?php

it('redirects the landing page to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
