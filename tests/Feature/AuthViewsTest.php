<?php

test('login page uses the application login view', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Account access')
        ->assertSee('Sign in to manage your business workspace.')
        ->assertSee('data-bookresa-password-toggle', false)
        ->assertSee('logo.png')
        ->assertDontSee('Run bookings, customers and your workspace from one place.');
});

test('registration page uses the application registration view', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create account')
        ->assertSee('Create your account first.');
});

test('guest auth pages use the dedicated BookResa session cookie', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertCookie(config('session.cookie'));
});

test('guest auth pages are not cached with stale csrf tokens', function (): void {
    foreach ([route('login'), route('register'), route('password.request')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');
    }
});
