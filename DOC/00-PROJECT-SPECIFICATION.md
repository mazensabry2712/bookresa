# BookResa — Complete Product & Technical Requirements

Source of truth for the complete BookResa product and technical specification.

## Project Name
BookResa

## Product
Business Booking SaaS Platform

## 1. Project Overview
BookResa is a Multi-Tenant SaaS Web Platform for small and medium-sized businesses that need to manage bookings, appointments, customers and services from one place.

The platform is one shared product, not a separate website or software system rebuilt for every company.

Core idea:
One Platform → Business creates Account → isolated Workspace/Tenant → choose Modules → own Dashboard → public Booking Page.

The platform must support a large number of Businesses without rebuilding the application per customer.

## 2. Target Customers
Small Businesses, Startups, Local Businesses, Small & Medium Businesses, Service-based Businesses and Businesses that manage appointments/bookings.

Examples: Clinics, Dental Clinics, Beauty Salons, Barbers, Gyms, Fitness Studios, Training Centers, Tutors, Consultants, Photography Businesses, Repair/Service Businesses, Small Agencies and similar businesses.

The product is not limited to individual businesses. A future Business can have multiple Employees, Services and Branches.

## 3. Core Concept — Workspace
Each Business gets an isolated Workspace inside BookResa.

Example:
BookResa → Ahmed Dental Clinic → Workspace
BookResa → Mohamed Barber → Workspace
BookResa → ABC Training Center → Workspace
BookResa → XYZ Consulting → Workspace

Every Workspace is isolated from every other Business.

## 4. Multi-Tenant Architecture
BookResa must be designed as a true Multi-Tenant SaaS application.

Each Business represents one Tenant.

Tenant-owned records must carry tenant_id where appropriate.

Example Booking fields:
id, tenant_id, customer_id, service_id, staff_id, date/starts_at, start/end time, status, payment_status.

The backend determines the current Tenant from the authenticated user's membership/context. A tenant_id coming from the frontend is never trusted as authorization.

Cross-Tenant Data Access must be blocked for URLs, request parameters, forms and API-like calls.

## 5. User Types
### Platform Admin
Can view/manage Businesses, Users, Plans, Subscriptions, Platform Revenue, Platform Settings, suspension/activation and Support.

### Business Owner
Can manage Business Profile, Services, Staff, Customers, Bookings, Calendar, Payments, Subscription, Settings and Reports.

### Business Staff
Business employees use RBAC roles such as Manager, Receptionist and Staff Member.

Manager: broad business permissions.
Receptionist: Bookings, Customers, Calendar.
Staff Member: own schedule, assigned bookings and booking status updates.

## 6. Business Registration Flow
Register → Verify Account → Create Business → Select Business Type → Configure Workspace → Select Required Modules → Set Services → Set Working Hours → Add Staff → Workspace Ready.

## 7. Business Profile
Each Business has Business Name, Logo, Cover Image, Description, Business Type, Phone, Email, Location, Address, Social Links, Working Hours and Booking URL.

Examples: platform.com/ahmed-clinic or platform.com/ahmed-clinic/book.

## 8. Modular Workspace
After Business creation, the Owner selects the modules needed by the Business.

Example modules: Appointments, Calendar, Customers, Services, Staff, Payments, Notifications. Future/plan-gated examples: Invoices, Inventory and Multiple Branches.

Dashboard module access is determined by Plan + Tenant Module Configuration + User Permissions.

## 9. Core Modules — Services
Business Owner can create, edit, delete, price, duration, buffer time and enable/disable services.

Example: Haircut — Price 150 EGP — Duration 30 minutes — Buffer 10 minutes.

## 10. Staff Management
Business Owner can add/edit/remove staff, assign services, set working hours, days off and individual availability.

Example: Ahmed Monday 10:00–18:00; Mohamed Monday 14:00–22:00.

## 11. Working Hours
Business controls working days, opening/closing times, breaks, holidays and special working hours.

Staff schedules and exceptions are also supported.

## 12. Booking Engine
Booking Engine is a Core Service.

Availability depends on Business Working Hours, Staff Working Hours, Service Duration, Buffer Time, Existing Bookings, Days Off, Holidays, Special Working Hours, Booking Rules and Tenant/Business Timezone.

Double Booking must be prevented even for concurrent requests.

Frontend availability is advisory only. Database transaction/locking is authoritative.

## 13. Customer Booking Flow
Public flow: Business → Service → Staff when applicable → Date → Available Time → Name → Phone → Email according to Business settings → Payment according to policy → Confirmation → Booking Reference.

Customer Account is not required by default.

## 14. Public Booking Page
Each Business has a public booking page showing Business Name, Logo, Description, Services, Prices, Staff, Available Times, Location, Contact Information and Book Now.

The page is responsive, mobile-first, Blade server-rendered and optimized for low latency and SEO.

## 15. Calendar
Views: Day, Week, Month.

Booking cards show Customer, Service, Staff, Start Time, End Time, Status and Payment Status.

Actions: Confirm, Cancel, Reschedule, Complete, Mark as No-show.

## 16. Booking Status
Booking statuses: Pending, Confirmed, Completed, Cancelled, Rescheduled, No-show.

Payment statuses are separate: Unpaid, Partially Paid, Paid, Refunded.

## 17. Customer Management
Every Business has a Customer Database.

Customer Profile: Name, Phone, Email, Total Bookings, Completed Bookings, Cancelled Bookings, No-shows, Total Spent, Upcoming Booking and Booking History.

## 18. Payments
Two separate financial contexts:
1. Platform Subscription Payment: Business Owner → BookResa.
2. Business Customer Payment: Customer → Business.

Payment architecture must support Full Payment, Deposit and Pay Later.

BookResa owns a provider-neutral Payment Core. MVP provider is Kashier. Future gateways such as Stripe, Paymob, Fawry or others must be addable without rewriting Booking, Subscription or Billing.

Provider-specific code stays under Infrastructure/Payments. Domain services depend on a PaymentGateway contract, never on a gateway SDK.

Kashier lifecycle: Create Payment/Session → secure checkout → webhook → verify signature → idempotency check → persist result → update Booking/Subscription → queue notifications.

Browser redirect alone does not confirm payment.

## 19. Subscription System
Supported billing periods may include Monthly, 3 Months, 6 Months and Yearly.

Subscription fields: Business/Tenant, Plan, Start Date, End Date, Status, Price and Payment Status.

Statuses: Trial, Active, Expired, Suspended, Cancelled.

Historical subscription/billing records preserve the pricing inputs used at calculation time.

## 20. Customer-Based Pricing / Usage-Based Fee
Each Plan has an included number of unique customers.

Example: Plan 299 EGP/month, Included Customers 20, Current Customers 27, Additional Customers 7.

Additional usage is billed using an Admin-configurable per-customer price.

## 21. Customer Count Definition
Customer = Unique customer profile inside the Workspace/Tenant.

If Ahmed books 10 times: Customers = 1, Bookings = 10.

Customer usage must never equal booking count and the same customer must not be counted twice within one Business.

Initial identity strategy should normalize customer phone data to avoid obvious formatting duplicates.

## 22. Usage Billing
System exposes Current Customer Count, Included Customer Limit, Additional Customer Count, Additional Fee and Total Subscription Cost.

Example: Professional, Base Price 500 EGP, Included 20, Current 35, Additional 15, Usage = 15 × X EGP, Final = Base + Usage.

Owner must see the calculation clearly.

## 23. Admin Pricing Control
Platform Admin can change Plan Price, Included Customer Limit, Additional Customer Price, Billing Period and Features/Modules included in a Plan.

Normal pricing changes must not require source-code changes.

## 24. Subscription Upgrade / Downgrade
Business can Upgrade, Downgrade, Renew and Cancel.

Downgrade policy must be explicit and configurable.

Example: 35 customers on a plan with 20 included does not delete customers.

Recommended MVP default: downgrade takes effect at the next billing boundary. Over-limit Businesses either continue with usage fees or are restricted from creating new customer profiles according to configurable policy.

## 25. Notifications
Customer: Booking Confirmation, Cancellation, Reschedule, Appointment Reminder.

Business: New Booking, Cancellation, Payment Received, Subscription Expiry, Usage Warning.

Use Laravel Notifications and queues where asynchronous delivery is appropriate.

## 26. Usage Notifications
Before limit: approaching warning.
At limit: included limit reached.
After limit: show additional customers and applicable fee.

## 27. Business Dashboard
Show Business Name, Today's Bookings, Upcoming Bookings, Today's Revenue, Customers, Employees, Subscription Status and Customer Usage.

Example: Customers 17/20, Bookings Today 12, Revenue Today 2,400 EGP, Subscription Professional, Expires 20 Dec.

## 28. Platform Admin Dashboard
Sections: Businesses, Users, Plans, Subscriptions, Payments, Usage, Bookings, Reports, Support and Settings.

Metrics: Total Businesses, Active Businesses, Trial Businesses, Expired Businesses, Total Subscriptions, Monthly Recurring Revenue, Customer Usage, Businesses Over Customer Limits and Additional Usage Revenue.

## 29. Business Isolation
Strict Tenant Isolation is mandatory.

Example: Ahmed Clinic customers Ahmed, Mohamed and Sara must never be visible to Mohamed Barber.

Backend must prevent cross-tenant access even if a user manipulates URL IDs, request fields or tenant_id.

## 30. Security Requirements
Secure Authentication, Password Hashing, HTTPS, RBAC, Tenant Isolation, Server-Side Authorization, Input Validation, Rate Limiting, Secure Sessions/Tokens, Audit Logs, Database Backups, Error Logging and Secure Payment Integration.

Also required: CSRF protection for browser forms, safe file validation, payment webhook signature verification, payment idempotency and no raw card PAN/CVV storage.

## 31. Responsive Design
Support Desktop, Laptop, Tablet and Mobile.

Public Customer Booking Page is mobile-first.

Initial UI themes: Light Mode and Dark Mode.

## 32. MVP
The current MVP release scope includes the operational platform already defined by this specification:

Must Have:
Authentication, Business Creation, Workspace, Business Profile, Services, Staff, Working Hours, Calendar, Availability, Bookings, Customers, Public Booking Page, Booking Notifications, Subscription, Customer Usage Tracking, Payments, Platform Admin Dashboard and basic Reports.

Payments in the MVP use the provider-neutral PaymentGateway architecture with Kashier as the first provider. Booking payment modes include Full Payment, Deposit and Pay Later. Real Kashier sandbox end-to-end verification remains a release-gate activity until completed against the real test environment.

Later / Post-MVP:
Invoices, additional payment providers beyond the current MVP provider, WhatsApp, SMS, Multiple Branches, Inventory, Recurring Bookings, CRM, Mobile App, Advanced Analytics, advanced tax/invoicing capabilities and other premium modules.

Localization, themes, SEO, security, tenant isolation and scalable architecture are MVP foundations, even when some production hardening tasks are completed separately.

## 33. Recommended Technical Architecture
Frontend: Blade + Alpine.js + Tailwind CSS + Vite.

Backend: Laravel application with Authentication, Tenant Management, RBAC, Booking Engine, Availability Engine, Customer, Services, Staff, Calendar, Payment Core, Subscription, Usage Billing, Notifications, SEO and Reporting modules.

Database: MySQL 8.x.

Performance layer: Redis-ready Cache/Queues/Locks and OPcache in production.

Architecture style: Modular Monolith.

Booking Engine and Tenant Management are Core Services.

## 34. Important Technical Principle
Do not build Clinic Website, Barber Website or Gym Website implementations.

Build Core Platform + Tenant + Configurable Modules.

Business Type is used for onboarding defaults, recommended modules and configuration. It never creates a separate application implementation.

## 35. Future Scalability
Support Multiple Branches, Multiple Locations, More Staff, More Services, More Customers, More Business Types, Additional Modules, API, Third-party Integrations and Mobile Applications without a fundamental core redesign.

## 36. Product Goal
The goal is not merely an Online Booking Website.

BookResa is a Business Management Workspace for Small and Medium Service Businesses.

Businesses create a workspace in minutes, operate bookings, manage customers/services/staff, use the calendar and expand through modules.

## 37. Basic User Journey
Business Owner → Create Account → Create Business → Choose Business Type → Select Required Features → Configure Workspace → Add Services → Add Staff → Set Working Hours → Publish Booking Page → Customers Start Booking → Business Manages Bookings → Subscription + Usage Billing.

## 38. Core Business Model
Primary revenue: Subscription.

Additional revenue: Usage-Based Revenue from additional unique customers.

Future revenue: Payment Transaction Fees and Optional Premium Modules.

MVP revenue model: Subscription + Customer Usage Fee.

## 39. Required Pre-Production Agreement
Before production code, agree on:
1. System Architecture
2. Database ERD
3. User Roles & Permissions
4. API Structure
5. Booking/Availability Logic
6. Multi-Tenant Strategy
7. Subscription Architecture
8. Usage Billing Logic
9. Security Plan
10. Deployment Architecture
11. Backup Strategy
12. Development Timeline
13. MVP Development Cost
14. Post-MVP Development Cost

## 40. Short Developer Definition
BookResa is a multi-tenant SaaS platform for small and medium-sized service businesses. Each business creates an account and receives an isolated Workspace inside BookResa.

The workspace is configurable and modular. Businesses activate tools such as services, staff, customers, calendar, appointments, payments, notifications and reports.

Each business receives a public booking page that customers can use without necessarily creating an account. Customers choose service, staff, date and available time, provide contact details, optionally pay, and receive a Booking Reference.

Plans include a defined number of unique customers. When the Business exceeds that limit, additional usage fees are automatically calculated from the number of additional unique customer profiles. Pricing is configurable from the Platform Admin Dashboard without source-code changes.

BookResa must be a true multi-tenant system with strict tenant isolation, RBAC, scalable booking/availability logic and modular architecture.

## 41. Agreed Technical Baseline
Framework: Laravel 13.
PHP: 8.4.
Database: MySQL 8.x.
Frontend: Blade, Alpine.js, Tailwind CSS, Vite.
Architecture: Modular Monolith.
Tenancy: Shared MySQL database with tenant_id.
Authentication: Laravel/Fortify.
RBAC: spatie/laravel-permission.
Audit: spatie/laravel-activitylog.
Sitemap: spatie/laravel-sitemap.
Image processing: intervention/image.
Testing: Pest.
Static Analysis: Larastan.
Formatting: Laravel Pint.
Development debugging: Debugbar only in development.
Performance: OPcache + Redis-ready cache/queues/locks.
Payments: Provider-neutral PaymentGateway; MVP Kashier.
Languages: Arabic + English.
Directions: RTL + LTR.
Themes: Light + Dark.
SEO: title, description, canonical, hreflang, Open Graph, social image, JSON-LD, sitemap and robots.txt.

## 42. Performance Principles
Performance is a first-class architecture requirement.

Use Blade SSR, minimal JavaScript, Alpine.js for local interaction, optimized MySQL queries, composite indexes, eager loading, pagination, targeted SELECT fields, no N+1, smart caching, queues, Redis in production and OPcache.

Do not use stale cache as authoritative availability.

Heavy or non-critical work must be queued.

Public booking pages receive the highest performance priority.

Octane/FrankenPHP is an optional future optimization after real measurement, not an MVP requirement.

## 43. Localization, Theme and SEO
Initial locales are Arabic and English.

Arabic is RTL; English is LTR.

Public/business content can support localized names/descriptions.

Light/Dark are first-class UI modes using centralized design tokens.

SEO targets public pages. Private Dashboard/Admin pages are non-indexable.

Public pages support metadata, canonical URLs, hreflang, Open Graph, JSON-LD, sitemap and robots.txt.

## 44. Payment Gateway Architecture
Application domain depends on PaymentGateway abstraction.

Provider adapters live under Infrastructure/Payments.

BookResa Booking, Subscription and Billing never import Kashier classes directly.

Kashier is the first provider. Adding another provider must require a new adapter, configuration and tests rather than a rewrite of the core domains.

Webhooks must be authenticated, idempotent and fast. The webhook result is the trusted payment state.

## 45. Package Philosophy
Prefer Laravel-native functionality first.

Use only focused external dependencies with clear value.

Planned runtime packages: Fortify, Spatie Permission, Spatie Activitylog, Spatie Sitemap and Intervention Image.

Development tools: Pest, Pest Laravel plugin, Larastan, Pint and Debugbar.

Not planned for MVP without a new requirement: stancl/tenancy, Cashier, Livewire, Jetstream, Passport, Scout, Media Library, GraphQL, microservices and multiple frontend frameworks.

## 46. Local and Production Environment
Local development target: Windows + Laravel Herd + PHP 8.4 + MySQL. Redis is optional locally but supported by architecture.

Production target: web server/Nginx + Laravel/PHP + MySQL + Redis + queue workers + scheduler + persistent storage + HTTPS + OPcache.

Backups must include database and important files, with retention, offsite copies and restore testing.

## 47. Documentation Rule
The DOC folder is the source of truth for BookResa product and architecture decisions.

Code changes that alter agreed architecture must first update the relevant technical decision/document.