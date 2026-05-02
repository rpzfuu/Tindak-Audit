<?php

use App\Models\User;

beforeEach(function () {
    $this->seed();
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::where('nik', '19990001')->firstOrFail();

    $response = $this->post('/login', [
        'nik' => $user->nik,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard.index', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::where('nik', '19990001')->firstOrFail();

    $this->post('/login', [
        'nik' => $user->nik,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::where('nik', '19990001')->firstOrFail();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
