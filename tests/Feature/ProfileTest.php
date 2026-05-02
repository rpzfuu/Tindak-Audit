<?php

use App\Models\User;

beforeEach(function () {
    $this->seed();
});

test('profile page is displayed', function () {
    $user = User::where('nik', '19990001')->firstOrFail();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});
