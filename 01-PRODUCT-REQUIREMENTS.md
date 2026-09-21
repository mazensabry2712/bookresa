# BookResa — Product Requirements

## Product definition
BookResa is a multi-tenant SaaS platform for small and medium service businesses. It is one platform, not separate websites or applications per business.

Each Business:
- creates an account;
- receives an isolated Workspace/Tenant;
- selects needed modules;
- configures services, staff and working hours;
- publishes a public booking page;
- manages customers, bookings and calendar;
- pays a subscription;
- may incur usage fees based on unique customers.

## Target businesses
Clinics, dental clinics, salons, barbers, gyms, fitness studios, training centers, tutors, consultants, photographers, repair/service businesses, agencies, and similar appointment-driven businesses.

## Roles
### Platform Admin
Businesses, users, plans, subscriptions, platform revenue, settings, suspension/activation and support.

### Business Owner
Business profile, services, staff, customers, bookings, calendar, payments, subscription, settings and reports.

### Business Staff
Tenant members with configurable RBAC roles such as Manager, Receptionist and Staff Member.

## Onboarding
Register → Verify → Create Business → Select Business Type → Configure Workspace → Select Modules → Add Services → Set Working Hours → Add Staff → Publish.

## Workspace
One Business maps to one Tenant/Workspace in the MVP. Tenant data must be isolated from every other tenant.

## Public booking
Customer does not need an account by default:
Business → Service → Staff (if applicable) → Date → Available Time → Customer Details → Payment policy → Confirmation → Booking Reference.

## Services
Name, description, price, duration, buffer, active state and staff assignments.

## Staff
Profile, role, assigned services, working hours, days off and availability.

## Scheduling
Business working days, opening/closing, breaks, holidays, special hours, staff schedules and staff exceptions.

## Booking statuses
Pending, Confirmed, Completed, Cancelled, Rescheduled, No-show.

## Payment statuses
Unpaid, Partially Paid, Paid, Refunded.

## Customers
A Customer is one unique customer profile inside one Tenant. Multiple bookings by the same person remain one customer.

## Payments
Two business contexts:
1. Business → BookResa for subscription/billing.
2. Customer → Business for booking/service payment.

Both use a provider-neutral Payment Core.

## Subscriptions
Plans define price, billing period, included unique customers, additional customer unit price and enabled modules.

Statuses:
Trial, Active, Expired, Suspended, Cancelled.

## Usage billing
Additional customers = max(current unique customers - included limit, 0).
Usage charge = additional customers × additional customer price.
Final subscription charge = base price + usage charge.

Platform Admin controls pricing without source changes.

## Product principle
Business type is configuration/default behavior only. Never create ClinicApplication/SalonApplication/GymApplication code branches.
