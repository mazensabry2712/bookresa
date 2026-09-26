<?php

test('public home page renders the BookResa landing page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('BookResa')
        ->assertSee('Start free')
        ->assertSee('See how it works')
        ->assertSee('Bookings')
        ->assertSee('/logo.png')
        ->assertDontSee('The PHP Framework for Web Artisans');
});
