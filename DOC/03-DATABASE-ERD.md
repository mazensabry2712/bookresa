# BookResa — Database / ERD Specification

## Database
MySQL 8.x, InnoDB, utf8mb4, strict mode, foreign keys enabled.

Persist timestamps consistently (prefer UTC) and calculate schedules using the tenant/business timezone.

## Identity and tenancy
### users
Core user identity.

### tenants
Business Workspace. Suggested fields: id, slug, business_type_id, status, timestamps.

### tenant_memberships
User ↔ Tenant membership, role metadata and status. Unique tenant_id + user_id.

## Business configuration
### business_profiles
tenant_id, localized name/description, logo, cover, contact data, location, timezone, locale and booking settings.

### business_types
Reusable onboarding/default configurations.

### modules
Platform module catalog.

### tenant_modules
Modules enabled for a tenant.

### plan_modules
Modules included in a plan.

## Services and staff
### services
tenant_id, localized name/description, price_minor, currency, duration_minutes, buffer_minutes, is_active.

### staff_profiles
tenant_id, user_id, display_name, profile fields, status.

### service_staff
service_id + staff_id with unique pair.

## Scheduling
- business_working_hours
- business_breaks
- business_holidays
- special_working_hours
- staff_working_hours
- staff_days_off
- staff_availability

## Customers
### customers
tenant_id, normalized_phone, name, email, metadata, first_seen_at, last_seen_at.

Initial matching candidate is tenant_id + normalized_phone. Matching logic stays centralized.

## Bookings
### bookings
id, tenant_id, customer_id, service_id, staff_id nullable, starts_at, ends_at, status, payment_status, payment_method (nullable for legacy/internal bookings; `cash` or `online` for public bookings), booking_reference, notes, timestamps.

### booking_status_history
Immutable lifecycle/history entries.

## Payments
### payments
id, tenant_id nullable for platform context, payable_type, payable_id, gateway, gateway_reference, gateway_transaction_id, amount_minor, currency, status, payment_method, metadata, paid_at, timestamps.

## Current commission and settlement tables

### booking_commissions (current implementation)
Current columns: id, tenant_id, payment_id (unique), customer_id nullable, customer_ordinal, included_customer_limit, eligible, rate_basis_points, gross_amount_minor, refunded_amount_minor, commission_amount_minor, workspace_net_minor, currency, status, metadata and timestamps. The row is payment-linked and reflects the legacy customer-profile ordinal policy; it is not a monthly booking-allocation record.

### workspace_payouts (current manual ledger)
Current columns: id, tenant_id, reference (unique), currency, amount_minor, status, payout_method nullable, payout_reference nullable, notes nullable, created_by nullable, paid_at nullable, metadata and timestamps. Reserving a payout does not initiate a transfer; a finance operator records the external transfer reference after separately executing it.

### Monthly booking commission allocation (implemented on main; migration not yet deployed)
- `booking_commission_period_counters`: one locked next-ordinal row per tenant and workspace-local calendar month.
- `booking_commission_allocations`: one immutable row per confirmed booking, storing period/timezone, ordinal, free allowance, enabled flag, rate, booking amount/currency, commission snapshot and policy version.
- `booking_commissions` retains historical payment-keyed rows and now links new online-payment rows to a booking allocation, including the monthly ordinal and free allowance. Paid/refunded payment attempts are reconciled in stable payment-ID order; combined commission is capped by the allocation snapshot.

The first qualifying event is the first transition to `confirmed`. Each booking consumes at most one slot after rescheduling/re-confirming; cancellation or no-show does not silently decrement the counter. Commission is off by default and independent from online-payment availability. The monthly policy uses a separate 1,000-basis-point default key so the retired customer-profile rate cannot override it. Historical ledger rows are not backfilled or rewritten.

Cash bookings receive an ordinal, but this payment ledger still represents verified online payment amounts only. Cash collection, commission receivable/remittance, monthly finance views, and external settlement remain open. The new schema/code is on `main` but has not been deployed to Hostinger. Review DOC/21 and the production release gate before deployment.

## Billing
### plans
name, price_minor, currency, billing_period, included_customer_limit, additional_customer_price_minor, active.

### subscriptions
tenant_id, plan_id, start_at, end_at, status, pricing snapshot, payment metadata.

### usage_periods / usage_charges
Measured unique-customer usage and immutable pricing inputs used to calculate the charge.

## Notifications / audit
Laravel notification records plus Spatie activity log storage.

## Money
Never use floating point for financial values. Example: 299.50 EGP → 29950 minor units.

## Indexing
Indexes are based on real access patterns. Typical patterns:
- tenant_id + status
- tenant_id + date/range
- tenant_id + staff_id + date/range
- tenant_id + customer_id
- tenant_id + service_id
- tenant_id + slug

Validate with EXPLAIN and measured query plans.
