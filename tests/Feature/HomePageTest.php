<?php

use IlluminateFoundationTestingRefreshDatabase;

uses(RefreshDatabase::class);

test('public home page renders the BookResa landing page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('BookResa')
        ->assertSee('Start with BookResa')
        ->assertSee('Explore features')
        ->assertSee('Booking management')
        ->assertSee('Create a BookResa workspace')
        ->assertDontSee('The PHP Framework for Web Artisans');
});
