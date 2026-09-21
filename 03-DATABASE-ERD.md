# BookResa — Database / ERD Specification

## Database
- MySQL 8.x
- InnoDB
- utf8mb4
- strict mode
- foreign keys
- consistent UTC persistence; tenant timezone for schedule calculations

## Identity and tenancy
### users
Core identity.

### tenants
Business workspace. Suggested fields: id, slug, business_type_id, status, timestamps.

### tenant_memberships
User ↔ tenant membership. Unique tenant_id + user_id.

## Business configuration
### business_profiles
tenant_id, localized name/description, logo, cover, contact data, location, timezone, locale and booking settings.

### business_types
Reusable default configurations.

### modules
Platform module catalog.

### tenant_modules
Tenant-enabled modules.

### plan_modules
Plan-enabled modules.

## Services and staff
### services
tenant_id, localized name/description, price_minor, currency, duration_minutes, buffer_minutes, is_active.

### staff_profiles
tenant_id, user_id, display name/profile/status.

### service_staff
service_id + staff_id, unique pair.

## Scheduling
business_working_hours
business_breaks
business_holidays
special_working_hours
staff_working_hours
staff_days_off
staff_availability

## Customers
### customers
tenant_id, normalized_phone, name, email, metadata, first_seen_at, last_seen_at.

Primary candidate identity rule: tenant_id + normalized_phone. Keep matching logic centralized.

## Bookings
### bookings
id, tenant_id, customer_id, service_id, staff_id nullable, starts_at, ends_at, status, payment_status, booking_reference, notes, timestamps.

### booking_status_history
Lifecycle history.

## Payments
### payments
id, tenant_id nullable for platform payment context, payable_type, payable_id, gateway, gateway_reference, gateway_transaction_id, amount_minor, currency, status, payment_method, metadata, paid_at, timestamps.

## Billing
### plans
name, price_minor, currency, billing_period, included_customer_limit, additional_customer_price_minor, active.

### subscriptions
tenant_id, plan_id, start_at, end_at, status, pricing snapshot, payment metadata.

### usage_periods / usage_charges
Measured customer usage and immutable pricing snapshot used for billing.

## Indexing
Use indexes based on real access patterns. Expected tenant-owned patterns:
- tenant_id + status
- tenant_id + date/range
- tenant_id + staff_id + date/range
- tenant_id + customer_id
- tenant_id + service_id
- tenant_id + slug

Validate with real EXPLAIN/query plans.
