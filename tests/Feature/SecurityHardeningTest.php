<?php

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('web responses include baseline security headers', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('audit logger records sensitive application events without secrets', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    app(AuditLogger::class)->log(
        'security.test_event',
        $user,
        [
            'action' => 'test',
            'tenant_id' => 123,
        ],
    );

    $activity = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'security.test_event')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->properties['tenant_id'] ?? null)->toBe(123)
        ->and($activity->properties['password'] ?? null)->toBeNull()
        ->and($activity->properties['api_key'] ?? null)->toBeNull();
});
