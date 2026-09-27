<?php

test('login page uses the application login view', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Sign in to manage your business workspace.');
});

test('registration page uses the application registration view', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create account')
        ->assertSee('Create your account first.');
});

test('password reset request page uses the application view', function (): void {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot password?');
});
