<?php

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Notifications\Auth\EmailVerificationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('registration sends an email OTP and redirects to a valid post-registration location', function (): void {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Verified Candidate',
        'email' => 'verified-candidate@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    expect(auth()->check())->toBeTrue();

    $user = User::query()->where('email', 'verified-candidate@example.com')->firstOrFail();
    Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function ($notification): bool {
        return $notification instanceof EmailVerificationOtpNotification
            && preg_match('/\A\d{6}\z/', $notification->code) === 1;
    });
});

test('unverified users are redirected to the verification notice before onboarding', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('onboarding.business.create'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users can access the verification-protected onboarding route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding.business.create'))
        ->assertOk();
});

test('signed verification link marks the user verified', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(30),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('onboarding.business.create').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});


test('verified tenant members are redirected to their workspace dashboard', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $tenant = Tenant::query()->create([
        'slug' => 'verified-workspace',
        'status' => TenantStatus::Active,
        'settings' => [
            'onboarding' => [
                'completed' => false,
            ],
        ],
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(30),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('dashboard', ['tenant' => $tenant->slug, 'verified' => 1]));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('unverified users can request another verification email', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->withHeader('Referer', route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
});

test('verification ignores a stale intended dashboard destination for a new account', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(30),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $this->actingAs($user)
        ->withSession(['url.intended' => '/dashboard'])
        ->get($url)
        ->assertRedirect(route('onboarding.business.create').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});


test('a valid email verification OTP marks the user verified', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);

    $user->sendEmailVerificationNotification();

    $code = '';
    Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function ($notification) use (&$code): bool {
        if (! $notification instanceof EmailVerificationOtpNotification) {
            return false;
        }

        $code = $notification->code;

        return true;
    });

    expect($code)->toMatch('/\A\d{6}\z/');

    $this->actingAs($user)
        ->post(route('verification.otp.verify'), ['code' => $code])
        ->assertRedirect(route('onboarding.business.create').'?verified=1')
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

test('an incorrect email verification OTP does not verify the user', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    $code = '';
    Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function ($notification) use (&$code): bool {
        if (! $notification instanceof EmailVerificationOtpNotification) {
            return false;
        }

        $code = $notification->code;

        return true;
    });

    $wrongCode = str_pad((string) (((int) $code + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.otp.verify'), ['code' => $wrongCode])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email verification OTP expires and cannot be used after its cache entry is gone', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    $code = '';
    Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function ($notification) use (&$code): bool {
        if (! $notification instanceof EmailVerificationOtpNotification) {
            return false;
        }

        $code = $notification->code;

        return true;
    });

    \Illuminate\Support\Facades\Cache::flush();

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.otp.verify'), ['code' => $code])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code', __('app.verification_code_expired'));

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email verification OTP blocks repeated incorrect attempts', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    $code = '';
    Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function ($notification) use (&$code): bool {
        if (! $notification instanceof EmailVerificationOtpNotification) {
            return false;
        }

        $code = $notification->code;

        return true;
    });

    $wrongCode = str_pad((string) (((int) $code + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

    for ($attempt = 1; $attempt < 5; $attempt++) {
        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.otp.verify'), ['code' => $wrongCode])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('code');
    }

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.otp.verify'), ['code' => $wrongCode])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors('code', __('app.verification_code_too_many_attempts'));

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification OTP endpoints require authentication', function (): void {
    $this->post(route('verification.otp.verify'), ['code' => '123456'])
        ->assertRedirect(route('login'));

    $this->post(route('verification.otp.resend'))
        ->assertRedirect(route('login'));
});
