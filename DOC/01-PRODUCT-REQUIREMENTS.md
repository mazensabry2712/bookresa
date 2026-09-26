# BookResa — Product Requirements

## 1. Product definition
BookResa is a multi-tenant SaaS platform for small and medium-sized service businesses that need booking, appointments, customers and service management from one place.

It is one platform, not a separate application or website per business.

Each Business:
- creates an account;
- receives an isolated Workspace/Tenant;
- selects the modules it needs;
- configures services, staff and working hours;
- publishes a public booking page;
- manages customers, bookings and calendar activity;
- pays a subscription;
- may incur additional usage fees based on unique customers.

## 2. Target customers
- Small businesses
- Startups
- Local businesses
- Small and medium businesses
- Service-based businesses
- Appointment/scheduled-service businesses

Examples: clinics, dental clinics, beauty salons, barbers, gyms, fitness studios, training centers, tutors, consultants, photography businesses, repair/service businesses, small agencies and similar businesses.

The product must support both solo owners and businesses with multiple employees/services, with future support for branches and multiple locations.

## 3. Core concept — Workspace
Each Business has a Workspace inside BookResa.

Example:
BookResa
→ Ahmed Dental Clinic → Workspace
→ Mohamed Barber → Workspace
→ ABC Training Center → Workspace
→ XYZ Consulting → Workspace

Workspaces are isolated. Business A cannot access Business B data.

## 4. Multi-tenant architecture
Every Business represents a Tenant in the MVP.

Tenant-owned records carry tenant_id where appropriate.

The backend resolves tenant access from authenticated user membership/context. The frontend is never trusted to choose a tenant as proof of authorization.

Cross-tenant data access must be prevented even when a user manipulates:
- URL IDs
- form fields
- request parameters
- API-like calls

## 5. User types
### Platform Admin
Can manage businesses, users, plans, subscriptions, platform revenue, platform settings, suspension/activation and support.

### Business Owner
Can manage business profile, services, staff, customers, bookings, calendar, payments, subscription, settings and reports.

### Business Staff
Tenant members with role-based permissions. Example roles:
- Manager
- Receptionist
- Staff Member

The application uses RBAC.

## 6. Registration/onboarding
Register → Verify Account → Create Business → Select Business Type → Configure Workspace → Select Required Modules → Set Services → Set Working Hours → Add Staff → Workspace Ready.

## 7. Business profile
Business Name, Logo, Cover Image, Description, Business Type, Phone, Email, Location, Address, Social Links, Working Hours and public Booking URL.

Example public URL:
bookresa.com/ahmed-clinic
or
bookresa.com/ahmed-clinic/book

## 8. Modular Workspace
Business owners can choose needed modules.

Initial examples:
- Appointments
- Calendar
- Customers
- Services
- Staff
- Payments
- Notifications

Potential plan-gated modules:
- Invoices
- Inventory
- Multiple Branches

The Dashboard should expose enabled modules and show upgrade options for unavailable plan modules.

## 9. Services
Create, edit, delete, set price, duration, buffer time and active state.

Example:
Haircut — 150 EGP — 30 minutes — 10-minute buffer.

## 10. Staff management
Add, edit, remove staff, assign services, define working hours, days off and individual availability.

## 11. Working hours
Business-level rules include working days, opening/closing times, breaks, holidays and special working hours.

## 12. Booking engine
Availability is calculated from:
- business working hours
- staff working hours
- service duration
- buffer time
- existing bookings
- days off
- holidays
- booking rules

Double booking must be prevented, including concurrent booking attempts.

## 13. Customer booking flow
Business → Service → Staff if applicable → Date → Available Time → Customer Name → Phone → Email according to business settings → Payment according to payment policy → Confirmation → Booking Reference.

Customer account is not required by default.

## 14. Public booking page
Displays business identity, description, services, prices, staff, available times, location/contact information and booking action.

It must be responsive and mobile-first.

## 15. Calendar
Day, Week and Month views.

Booking cards show customer, service, staff, start/end, booking status and payment status.

Actions:
Confirm, Cancel, Reschedule, Complete, No-show.

## 16. Booking statuses
Pending, Confirmed, Completed, Cancelled, Rescheduled, No-show.

Payment statuses are separate:
Unpaid, Partially Paid, Paid, Refunded.

## 17. Customer management
Customer profile includes name, phone, email, booking counts, no-shows, total spent, upcoming booking and booking history.

## 18. Payments
Keep two business contexts separate:
1. Platform Subscription Payment — Business → BookResa.
2. Business Customer Payment — Customer → Business.

The payment architecture uses a provider-neutral gateway contract. MVP gateway: Kashier. Future providers must be addable without rebuilding Booking/Subscription/Billing.

MVP payment modes should support the architecture for full payment, deposits and pay-later flows.

## 19. Subscription system
Billing periods can include Monthly, 3 Months, 6 Months and Yearly.

Subscription fields include Business, Plan, Start Date, End Date, Status, Price and Payment Status.

Statuses:
Trial, Active, Expired, Suspended, Cancelled.

## 20. Customer-based usage fee
Each plan includes a number of unique customers.

Example:
Plan 299 EGP/month, 20 customers included, 27 current customers → 7 additional customers.

Additional customer price is configurable by Platform Admin without source-code changes.

## 21. Customer count definition
Customer = unique customer profile inside a Workspace/Tenant.

If one customer books 10 times:
Customers = 1
Bookings = 10

Customer usage is not booking count.

## 22. Usage billing
The system exposes:
- current customer count
- included customer limit
- additional customer count
- additional fee
- total subscription cost

The owner sees these values clearly.

## 23. Admin pricing controls
Platform Admin can change:
- plan price
- included customer limit
- additional customer price
- billing period
- plan features/modules

No code change should be required for normal pricing updates.

## 24. Upgrade/downgrade
Business can upgrade, downgrade, renew and cancel.

Downgrade behavior must be explicit and configurable. Existing customers are never deleted because of a plan limit.

Default recommended behavior: downgrade takes effect at the next billing boundary; over-limit customers either incur usage charges or customer creation is restricted according to policy.

## 25. Notifications
Customer:
- booking confirmation
- cancellation
- reschedule
- appointment reminder

Business:
- new booking
- cancellation
- payment received
- subscription expiry
- usage warning

## 26. Usage notifications
Before limit: approaching warning.
At limit: included limit reached.
After limit: show additional customer count and applicable usage fee.

## 27. Business dashboard
Show:
Business name, today's bookings, upcoming bookings, today's revenue, customers, employees, subscription status and customer usage.

## 28. Platform Admin dashboard
Show:
businesses, users, plans, subscriptions, payments, usage, bookings, reports, support and settings.

Metrics include business counts/statuses, subscriptions, recurring revenue, customer usage, businesses above limits and usage revenue.

## 29. Security/isolation
Strict tenant isolation, secure authentication, RBAC, server-side authorization, validation, rate limiting, secure sessions/tokens, audit logs, backups, error logging and secure payment integration.

## 30. Responsive design
Desktop, laptop, tablet and mobile. Public booking is mobile-first.

## 31. MVP
The current MVP scope includes:
Authentication, Business Creation, Workspace, Business Profile, Services, Staff, Working Hours, Calendar, Availability, Bookings, Customers, Public Booking Page, Booking Notifications, Subscription, Customer Usage Tracking, Payments, basic Reports and Platform Admin.

Payments are provider-neutral at the domain level and use Kashier as the current MVP provider. Booking payments support Full Payment, Deposit and Pay Later. Subscription payments use the same provider-neutral Payment Core but remain a separate financial context from customer booking payments.

Later / Post-MVP:
Invoices, additional payment providers, WhatsApp, SMS, Multiple Branches, Inventory, Recurring Bookings, CRM, Mobile App, Advanced Analytics and complex tax/invoicing capabilities.

The current backend implementation may be ahead of some originally planned roadmap wording; this requirements document describes the agreed current MVP target, while `DOC/15-IMPLEMENTATION-CHECKLIST.md` records what is actually implemented and what remains.

## 32. Technical architecture principle
Core Platform + Tenant + Configurable Modules.

Do not create separate Clinic/Salon/Gym applications.

## 33. Future scalability
Support future branches, locations, staff, services, customers, business types, modules, APIs, third-party integrations and mobile apps without a fundamental core redesign.

## 34. Product goal
BookResa is a Business Management Workspace for small and medium service businesses, not only an online booking website.

## 35. Core business model
Primary revenue:
Subscription.

Additional:
Unique-customer usage fees.

Future:
Payment transaction fees and premium modules.

## 36. Pre-production agreement
Before production code, agree on:
System Architecture, Database ERD, Roles/Permissions, API Structure, Booking/Availability Logic, Multi-Tenant Strategy, Subscription Architecture, Usage Billing, Security, Deployment, Backup, Timeline and Cost.
