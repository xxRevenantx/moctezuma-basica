<?php

test('public registration is disabled', function () {
    $this->get('/register')
        ->assertRedirect(route('login'));

    $this->post('/register', [
        'name' => 'Usuario no autorizado',
        'email' => 'registro-no-autorizado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
