# BookResa — MVP Roadmap

This roadmap is the product delivery sequence. The detailed architecture lives in DOC/02, functional requirements in DOC/01, backend completion status in DOC/15, production release tasks in DOC/17, and the frontend execution/design contract in DOC/18.

## Phase 0 — Foundation
Laravel 13, PHP 8.4, MySQL, authentication foundation, Blade layout, Arabic/English locale, RTL/LTR foundation, light/dark foundation, tenancy foundation, RBAC foundation, module catalog and documentation.

## Phase 1 — Identity and Tenancy
Users, tenants, memberships, verification, TenantResolver/current tenant context, tenant isolation, roles and permissions and Platform Admin foundation.

## Phase 2 — Business Workspace
Business types, business profile, modules, tenant modules, services, staff profiles, service assignments, working hours and onboarding workflow.

## Phase 3 — Booking Engine
Customers, public business/booking page, service/staff selection, availability, booking lifecycle, calendar, booking reference, rescheduling and concurrency protection.

## Phase 4 — Billing and Usage
Plans, subscriptions, pricing snapshots, unique customer counting, usage charges, owner usage display, upgrade/downgrade/cancel/reactivate/renewal/expiry lifecycle and plan/module entitlement.

## Phase 5 — Payments and Notifications
Provider-neutral Payment Core, Kashier adapter, secure checkout/session, signed webhook, server verification, idempotency, booking/subscription payment states and database/email notifications.

## Phase 6 — Platform Operations and Hardening
Platform Admin operations, reports, audit logs, rate limits, indexes/query review, queues, SEO, production build, Redis/OPcache, backup/restore and release QA.

## Frontend rollout
Frontend follows the same domain dependency order, but is executed through DOC/18:

F0 Design foundation and shared components  
F1 Authentication  
F2 Onboarding  
F3 Dashboard  
F4 Business/profile/settings  
F5 Services  
F6 Staff  
F7 Scheduling/availability  
F8 Bookings  
F9 Calendar  
F10 Customers  
F11 Payments  
F12 Public booking  
F13 Billing/subscriptions  
F14 Notifications  
F15 Reports  
F16 Platform Admin  
F17 System/error states and final polish

## Current scope rule
The current backend is ahead of the original roadmap wording in several areas, especially payments, reports and production hardening. Do not move an implemented capability back to "future" merely because an older phase description placed it later.

Use DOC/15 as the implementation truth and DOC/17 as the production release truth.

## Outside MVP unless separately approved
Branches, inventory, CRM, recurring bookings, advanced analytics, WhatsApp/SMS, mobile apps, complex tax/invoicing and marketplace payouts.

## Exit condition
The MVP is releasable only when the product workflows are implemented, automated checks are green, payment sandbox verification is complete, and the production readiness gate in DOC/17 is satisfied.
