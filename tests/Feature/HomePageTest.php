<?php

test('public home page renders the BookResa product home', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('BookResa')
        ->assertSee('Start free')
        ->assertSee('A simpler way to run your bookings.')
        ->assertSee('Customer booking')
        ->assertSee('/logo.png')
        ->assertDontSee('The PHP Framework for Web Artisans');
});


test('public home page supports Arabic navigation and copy', function (): void {
    $this->get(route('home').'?locale=ar')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('طريقة أبسط لإدارة حجوزاتك.')
        ->assertSee('حجز العميل');
});
