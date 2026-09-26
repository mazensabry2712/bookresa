<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Customer\Actions\CreateCustomer;
use App\Domain\Customer\Models\Customer;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function customerWorkspaceOwner(string $name = 'Customer Workspace Owner'): array
{
    $user = User::factory()->create([
        'name' => $name,
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => 'Customer Clinic',
        'name_en' => 'Customer Clinic',
        'name_ar' => 'عيادة العملاء',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$user, $tenant];
}

test('owner can list and create customers', function (): void {
    [$owner, $tenant] = customerWorkspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Customers');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('customers.store'), [
            'name' => 'Ahmed Ali',
            'phone' => '010-1234-5678',
            'email' => 'ahmed@example.com',
        ])
        ->assertRedirect(route('customers.index'));

    app(CurrentTenant::class)->set($tenant);

    $customer = Customer::query()->firstOrFail();

    expect($customer->name)->toBe('Ahmed Ali')
        ->and($customer->phone)->toBe('010-1234-5678')
        ->and($customer->normalized_phone)->toBe('201012345678');
});

test('owner can update customer details without changing tenant ownership', function (): void {
    [$owner, $tenant] = customerWorkspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    $customer = app(CreateCustomer::class)->handle([
        'name' => 'Old Name',
        'phone' => '01012345678',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('customers.update', $customer), [
            'name' => 'New Name',
            'phone' => '+20 10 1234 5678',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('customers.show', $customer));

    app(CurrentTenant::class)->set($tenant);
    $customer->refresh();

    expect($customer->name)->toBe('New Name')
        ->and($customer->phone)->toBe('+20 10 1234 5678')
        ->and($customer->normalized_phone)->toBe('201012345678')
        ->and($customer->tenant_id)->toBe($tenant->id);
});

test('duplicate normalized phone is rejected within the tenant', function (): void {
    [$owner, $tenant] = customerWorkspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    app(CreateCustomer::class)->handle([
        'name' => 'First Customer',
        'phone' => '01012345678',
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('customers.store'), [
            'name' => 'Second Customer',
            'phone' => '+20 10 1234 5678',
        ])
        ->assertSessionHasErrors('customer');
});

test('customers are tenant isolated for list and update', function (): void {
    [$ownerA, $tenantA] = customerWorkspaceOwner('Owner A');
    [$ownerB, $tenantB] = customerWorkspaceOwner('Owner B');

    app(CurrentTenant::class)->set($tenantA);
    $customerA = app(CreateCustomer::class)->handle([
        'name' => 'Tenant A Customer',
        'phone' => '01000000001',
    ]);

    $this->actingAs($ownerB)
        ->withSession(['tenant_id' => $tenantB->id])
        ->get(route('customers.index'))
        ->assertOk()
        ->assertDontSee('Tenant A Customer');

    $this->actingAs($ownerB)
        ->withSession(['tenant_id' => $tenantB->id])
        ->get(route('customers.show', $customerA))
        ->assertNotFound();

    $this->actingAs($ownerB)
        ->withSession(['tenant_id' => $tenantB->id])
        ->put(route('customers.update', $customerA), [
            'name' => 'Cross Tenant',
            'phone' => '01000000002',
        ])
        ->assertNotFound();

    app(CurrentTenant::class)->set($tenantA);
    expect($customerA->fresh()->name)->toBe('Tenant A Customer');
});

test('receptionist can view and manage customers', function (): void {
    [$owner, $tenant] = customerWorkspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    $receptionist = User::factory()->create([
        'name' => 'Front Desk',
        'email' => 'front-desk@example.com',
    ]);

    app(AddStaffMember::class)->handle($receptionist, 'receptionist');

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('customers.index'))
        ->assertOk();

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('customers.store'), [
            'name' => 'Walk In Customer',
        ])
        ->assertRedirect(route('customers.index'));

    app(CurrentTenant::class)->set($tenant);
    expect(Customer::query()->where('name', 'Walk In Customer')->exists())->toBeTrue();

    unset($owner);
});

test('staff without customer permission is forbidden', function (): void {
    [$owner, $tenant] = customerWorkspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    $staff = User::factory()->create([
        'email' => 'staff-customer-forbidden@example.com',
    ]);

    app(AddStaffMember::class)->handle($staff, 'staff');

    $this->actingAs($staff)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('customers.index'))
        ->assertForbidden();

    unset($owner);
});

test('customer management paginates large lists', function (): void {
    [$owner, $tenant] = customerWorkspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    Customer::factory()->count(21)->create([
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('customers.index'))
        ->assertOk()
        ->assertViewHas('customers', function ($customers): bool {
            return $customers->perPage() === 20
                && $customers->total() === 21;
        });
});
