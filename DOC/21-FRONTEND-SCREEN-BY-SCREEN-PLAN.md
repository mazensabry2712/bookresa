# BookResa — Frontend Screen-by-Screen Implementation Plan

> **Status:** Approved implementation plan for the frontend phase.
>
> **Product:** BookResa
>
> **Frontend architecture:** Laravel Blade + Tailwind CSS + Vite + minimal JavaScript.
>
> **Rule:** The existing backend contracts and business rules are the source of truth. The frontend must consume them without duplicating business logic.

---

## 1. Purpose

This document turns the frontend direction into an executable screen-by-screen plan.

It defines:

- the exact implementation order;
- every major screen and state;
- the relationship between screens and existing backend routes;
- the shared UI components required before feature pages;
- permissions and tenant/module awareness;
- Arabic/English and RTL/LTR requirements;
- light/dark theme requirements;
- responsive targets;
- public booking UX;
- Platform Admin UX;
- QA and completion criteria.

This file is a delivery checklist, not a visual moodboard.

---

## 2. Frontend principles

### 2.1 Product priorities

BookResa frontend priorities are:

1. Clarity
2. Speed of operation
3. Mobile usability
4. Consistency
5. Accessibility
6. Brand expression

Do not trade workflow clarity for decoration.

### 2.2 Architecture

The current application is Blade-first.

Use:

- Blade templates/layouts/components;
- Tailwind CSS;
- Alpine/minimal JavaScript where interaction requires it;
- Vite for asset compilation;
- server-rendered data from existing controllers/routes.

Do not introduce React/Vue/Nuxt/Inertia or a large UI kit unless a concrete repeated requirement justifies it.

### 2.3 Backend boundary

The frontend must not become a second business layer.

The backend remains authoritative for:

- tenancy;
- roles and permissions;
- module access;
- subscription usability;
- availability;
- booking concurrency;
- payment status;
- usage limits;
- billing lifecycle;
- signed payment confirmation;
- notification generation.

UI visibility is not a security boundary. Backend middleware, policies and services remain authoritative.

---

## 3. Brand and assets

### 3.1 Brand tokens

Use the existing BookResa visual direction:

| Token | Value |
|---|---|
| brand.navy | #1E2A44 |
| brand.indigo | #6366F1 |
| brand.coral | #FF7A66 |
| brand.soft | #EDEFF6 |
| brand.slate | #4B5563 |
| light surface | #FFFFFF |

Coral is an accent, not a primary background.

### 3.2 Logo rule

The BookResa logo is an existing project asset and must be reused.

Rules:

- use the existing logo asset;
- do not redraw the mark in CSS/SVG when the approved asset is available;
- use the full wordmark in desktop navigation when space allows;
- use the compact icon on mobile;
- keep the logo readable in dark mode;
- use the same source asset across authentication, application shell, public booking and relevant admin surfaces;
- do not create a second competing logo.

**Repository verification note:** the current tracked `main` branch `public/` directory currently exposes `favicon.ico` (0 bytes), `.htaccess`, and `index.php`; no named logo image is currently visible through the GitHub contents listing. If the logo exists only in the local working tree, it must be committed to the agreed public asset location before the final frontend release. Until then, the implementation must reference the existing local/approved logo path rather than inventing a new brand file.

### 3.3 Asset policy

Prefer:

- compressed SVG/PNG/WebP where appropriate;
- explicit dimensions for major images;
- lazy loading for non-critical images;
- normal `public/` asset URLs or Laravel `asset()` helpers;
- stored business logos/covers through the existing public storage disk URLs.

Do not duplicate business-uploaded assets into frontend-specific folders.

---

## 4. Shared application shell

Build the shell before feature pages.

### 4.1 Desktop shell

Components:

- App sidebar
- Header/topbar
- Workspace/business switch/context area if required
- Page header
- Notification trigger
- User menu
- Language switcher
- Theme switcher
- Main content container

Sidebar daily-work order:

1. Dashboard
2. Bookings
3. Calendar
4. Services
5. Staff
6. Customers
7. Payments
8. Subscription
9. Settings

### 4.2 Mobile shell

Components:

- compact topbar;
- menu drawer;
- page title;
- contextual page action;
- mobile-safe bottom/action placement where useful.

Do not simply compress the desktop sidebar.

### 4.3 Global UI states

Create reusable:

- button states;
- form field states;
- table states;
- skeleton loaders;
- empty states;
- error panels;
- success states;
- confirmation dialogs;
- destructive confirmations;
- toast notifications;
- pagination;
- badges;
- breadcrumbs where useful.

---

## 5. Auth screens — Phase F1

These are standalone public/authentication layouts.

### F1.1 Login

Purpose:
- sign in.

Required:
- email;
- password;
- remember option where supported;
- login action;
- forgot password;
- register link.

States:
- validation error;
- invalid credentials;
- rate limited;
- success redirect.

### F1.2 Register

Required:
- name;
- email;
- password;
- password confirmation.

Success:
- email verification flow.

### F1.3 Email verification

Required:
- clear verification state;
- resend verification;
- logout/back path;
- success/failure feedback.

### F1.4 Forgot password

Required:
- email;
- submit;
- success confirmation.

### F1.5 Reset password

Required:
- email/token;
- new password;
- confirmation;
- success redirect.

### F1 completion

- desktop;
- mobile;
- Arabic;
- English;
- RTL;
- LTR;
- light;
- dark.

---

## 6. Onboarding — Phase F2

Backend sequence:

Register → Verify → Create Business → Business Type → Workspace → Modules → Services → Working Hours → Staff → Ready.

### F2.1 Create business

Route:
- `onboarding.business.create`

Screen:
- workspace/business name;
- initial business basics;
- submit.

### F2.2 Business type

Part of onboarding/business creation or workspace progression depending current controller/view.

Must present:
- business type cards/options;
- concise descriptions;
- clear selection.

### F2.3 Workspace setup

Route:
- `onboarding.workspace`

Show progress:

1. Workspace
2. Modules
3. Services
4. Working hours
5. Staff
6. Ready

### F2.4 Modules

Route:
- `onboarding.workspace.modules`

UI:
- core modules locked/required;
- optional modules only when entitled by current subscription;
- clear selected/unselected state;
- explain unavailable upgrade path.

Do not present a module as available merely because the user can see it.

### F2.5 Services

Use the same services backend and UI component created later in Phase F3, with a focused onboarding variant.

### F2.6 Working hours

Focused onboarding version of scheduling.

### F2.7 Staff

Focused onboarding version of staff setup.

### F2.8 Workspace ready

Route:
- `onboarding.complete`

Show:
- completed steps;
- booking page/public URL;
- open dashboard;
- preview public booking page.

### F2 completion

A new business must reach a usable workspace with the fewest necessary decisions.

---

## 7. Dashboard — Phase F3

Route:
- `dashboard`

Middleware already protects:
- authentication;
- verification;
- tenant context;
- subscription usability.

### F3.1 Main dashboard

Sections:

- page header;
- New booking action;
- today's booking count;
- today's revenue;
- customers;
- upcoming bookings;
- attention/alerts;
- subscription/usage summary.

Do not use ten KPI cards.

### F3.2 Upcoming bookings panel

Columns/data:

- time;
- customer;
- service;
- staff;
- booking status;
- payment state where relevant.

### F3.3 Attention panel

Only render when useful:

- pending bookings;
- unpaid bookings;
- expiring subscription;
- usage threshold;
- usage limit reached;
- failed/attention payment states.

### F3.4 Role-specific dashboard behavior

Staff users may see only their operational scope.

Frontend must respect the server-returned filtered data and permissions.

---

## 8. Business profile and settings — Phase F4

Routes:

- `business.profile.edit`
- `business.profile.update`

### F4.1 Business profile

Fields:

- business name;
- localized description;
- business type;
- phone;
- email;
- location/address;
- social links;
- timezone.

Media:

- logo;
- cover image;
- replace/remove controls.

### F4.2 Booking settings

Fields include the backend-supported behavior:

- payment required/mode;
- full payment;
- deposit;
- pay later;
- deposit percentage;
- customer email requirement;
- customer limit policy.

Only show payment-specific controls when the payments module is available.

### F4.3 Appearance

Controls:

- light/dark;
- language;
- public branding preview where useful.

### F4.4 Settings navigation

Organize by responsibility:

- Business
- Booking
- Appearance
- Team
- Billing

Do not create one giant settings form.

---

## 9. Services — Phase F5

Routes:

- `services.index`
- `services.store`
- `services.update`
- `services.destroy`

### F5.1 Services list

Show:

- service name;
- duration;
- price;
- assigned staff count;
- status;
- actions.

Primary action:
- Add service.

### F5.2 Create service

Form groups:

1. Basic information
2. Duration and price
3. Booking behavior
4. Staff assignment
5. Optional business-specific details

### F5.3 Edit service

Same information architecture as create.

### F5.4 Delete confirmation

Show consequence and require explicit confirmation.

### F5.5 Empty state

Message:
- no services yet;
- why services are required;
- one Add service action.

---

## 10. Staff — Phase F6

Routes:

- `staff.index`
- `staff.store`
- `staff.update`
- `staff.status`

### F6.1 Staff list

Show:

- display name;
- role;
- services;
- schedule state;
- active/inactive.

### F6.2 Add staff

Fields:

- display name;
- phone;
- job title;
- role where allowed;
- initial status;
- service assignments where exposed.

### F6.3 Edit staff

Same structure as add.

### F6.4 Staff status

Dedicated active/inactive action.

### F6.5 Staff details

The details experience should aggregate:

- profile;
- assigned services;
- recurring hours;
- days off;
- availability/blocked periods;
- relevant booking information.

---

## 11. Scheduling and availability — Phase F7

Route root:
- `scheduling.index`

### F7.1 Scheduling overview

Tabs/sections:

- Business hours
- Breaks
- Holidays
- Special hours
- Staff hours
- Days off
- Availability

### F7.2 Business hours

Route:
- `scheduling.business-hours.update`

UX:
- weekly repeat editor;
- day enable/disable;
- start/end time;
- compact break display.

### F7.3 Breaks

Routes:
- `scheduling.breaks.store`
- `scheduling.breaks.destroy`

Show within relevant day/time context.

### F7.4 Holidays

Routes:
- `scheduling.holidays.store`
- `scheduling.holidays.destroy`

### F7.5 Special hours

Routes:
- `scheduling.special-hours.store`
- `scheduling.special-hours.destroy`

Clearly distinguish one-off special hours from recurring hours.

### F7.6 Staff hours

Route:
- `scheduling.staff-hours.update`

### F7.7 Staff days off

Routes:
- `scheduling.staff-days-off.store`
- `scheduling.staff-days-off.destroy`

### F7.8 Staff availability

Routes:
- `scheduling.staff-availability.store`
- `scheduling.staff-availability.destroy`

### F7.9 Scheduling UX rule

Do not expose backend scheduling complexity unless the user needs to operate it.

---

## 12. Bookings — Phase F8

Routes:

- `booking.management.index`
- `booking.management.show`
- `booking.management.status`
- `booking.management.reschedule`

### F8.1 Booking list

Filters:

- date/range;
- status;
- staff;
- service;
- customer search.

Record:

- booking reference;
- customer;
- service;
- staff;
- date/time;
- status;
- payment status.

### F8.2 Booking details

Structure:

- status header;
- customer;
- service;
- staff;
- date/time;
- price;
- payment;
- notes;
- timeline/history when provided;
- allowed actions.

### F8.3 Create booking

Use the existing backend create workflow and validation.

The UI must preserve entered values after validation errors.

### F8.4 Status action

Available actions must come from the valid backend lifecycle.

Do not hard-code invalid transitions in the UI.

### F8.5 Reschedule

UX:

- new date;
- new time;
- staff when relevant;
- availability feedback;
- clear conflict/error;
- preserve booking information.

### F8.6 Cancel

Destructive confirmation:
- explain what changes;
- confirm.

### F8 completion

Staff-role users must only interact with bookings they are authorized to access.

---

## 13. Calendar — Phase F9

Route:
- `calendar.index`

Supported modes:

- day;
- week;
- month.

### F9.1 Day view

Show:
- time grid;
- booking blocks;
- current day;
- staff/resource filtering;
- status.

### F9.2 Week view

Show:
- weekly columns;
- time grid;
- readable event blocks.

### F9.3 Month view

Show:
- month grid;
- booking count/details indicators;
- current date;
- day drill-down.

### F9.4 Calendar interactions

Where backend behavior permits:

- click booking → details;
- click available slot → create flow;
- navigate dates;
- filter staff.

Do not create client-side availability logic that conflicts with the server.

---

## 14. Customers — Phase F10

Routes:

- `customers.index`
- `customers.store`
- `customers.show`
- `customers.update`

### F10.1 Customer list

Show:

- name;
- contact;
- last booking;
- booking count;
- relevant status.

### F10.2 Add customer

Fields based on backend validation.

### F10.3 Customer details

Sections:

- profile;
- booking history;
- payment history when permitted;
- total bookings;
- completed;
- cancelled;
- no-show;
- total spent;
- upcoming booking.

### F10.4 Customer usage limit state

When the business is on a strict customer-limit policy:

- show clear limit warning;
- explain why creation is blocked;
- never hide a backend rejection.

---

## 15. Payments — Phase F11

Route:
- `payments.index`

### F11.1 Payments history

Show:

- booking/reference;
- customer;
- amount;
- status;
- date;
- provider/reference where useful.

Filter:
- payment status.

Pagination:
- mandatory for large datasets.

### F11.2 Booking payment state

Payment state must remain visually distinct from booking status:

- unpaid;
- partially paid;
- paid;
- refunded/failed where returned by backend.

### F11.3 Checkout result

Payment UI must trust signed/server-verified backend state, not a browser redirect alone.

---

## 16. Public booking — Phase F12

Canonical URL:

- `/{tenant:slug}/book`

Availability:
- `/{tenant:slug}/book/availability`

Booking:
- `POST /{tenant:slug}/book`

Confirmation:
- signed confirmation route.

Legacy booking routes remain supported by the backend for compatibility but the frontend should use canonical links.

### F12.1 Public business header

Show:

- business logo;
- business name;
- cover image when configured;
- description;
- contact/location where useful;
- language/theme controls when appropriate.

### F12.2 Service selection

Show:

- service name;
- description;
- duration;
- price.

If only one valid service exists, avoid unnecessary selection friction.

### F12.3 Staff selection

Rules:

- one valid staff member → skip unnecessary choice;
- multiple valid staff → show selection;
- no staff choice if internal scheduling does not require it.

### F12.4 Date selection

Show:

- available dates;
- unavailable dates;
- timezone clarity;
- loading state;
- no availability state.

### F12.5 Time selection

Show:

- available slots;
- selected state;
- unavailable state;
- mobile-safe tap targets.

### F12.6 Customer information

Required:
- phone;
- other required fields returned by backend.

Optional:
- email only when policy/configuration makes it optional.

Do not ask for unnecessary account creation by default.

### F12.7 Payment

Modes:

- full;
- deposit;
- pay later.

Always show:

- service;
- date/time;
- amount due;
- total;
- deposit explanation when applicable.

### F12.8 Confirmation

Show:

- booking reference;
- service;
- date/time;
- staff if applicable;
- payment state;
- business details;
- next actions;
- reschedule/cancel paths when supported.

### F12.9 Public booking quality bar

This flow must be tested first on mobile because it is customer-facing.

---

## 17. Billing and subscriptions — Phase F13

Routes:

- `billing.subscription`
- `billing.subscribe`
- `billing.subscription.checkout`
- `billing.subscription.renew`
- `billing.subscription.plan`
- `billing.subscription.cancel`
- `billing.subscription.reactivate`
- `billing.subscription.plan.clear`

### F13.1 Plans

Show:

- plan name;
- price;
- billing interval;
- included modules/limits;
- clear CTA.

### F13.2 Current subscription

Show:

- plan;
- status;
- period start/end;
- payment state;
- renewal state;
- pending plan change;
- cancellation state.

### F13.3 Usage

Show:

- current usage;
- limits;
- warning threshold;
- over-limit state;
- additional usage information where provided.

### F13.4 Upgrade/downgrade

Explain:

- current plan;
- selected next plan;
- effective date;
- effect on modules/limits.

### F13.5 Cancel

Confirm:
- cancellation timing;
- service access consequence.

### F13.6 Reactivate

Show:
- current cancellation schedule;
- effect of reactivation.

### F13.7 Renew

For expired subscriptions:
- clearly explain renewal;
- launch the backend payment flow.

---

## 18. Notifications — Phase F14

Notifications should be available from the global shell and a dedicated view if the current page structure exposes one.

Notification categories supported by backend:

- booking confirmation;
- cancellation;
- reschedule;
- payment;
- subscription expiry;
- usage threshold;
- usage limit/over-limit;
- reminders;
- business-side booking/payment alerts.

UI requirements:

- unread state;
- readable timestamp;
- useful short message;
- navigation to the relevant record when supported;
- no critical error communicated only through toast.

---

## 19. Reports — Phase F15

Tenant route:
- `reports.business`

Platform route:
- `admin.reports.index`

### F15.1 Business reports

Prioritize:

- bookings;
- revenue;
- customers;
- usage.

Use tables/summary blocks before decorative charts.

### F15.2 Platform reports

Prioritize:

- tenant/business counts;
- subscriptions;
- payments;
- usage revenue;
- operational trends returned by backend.

---

## 20. Platform Admin — Phase F16

Admin shell is separate from tenant shell.

Routes:

- `admin.dashboard`
- `admin.businesses.index`
- `admin.businesses.modules.index`
- `admin.businesses.modules.update`
- `admin.businesses.toggle-status`
- `admin.users.index`
- `admin.users.membership-toggle`
- `admin.users.platform-admin-toggle`
- `admin.subscriptions.index`
- `admin.subscriptions.toggle-status`
- `admin.payments.index`
- `admin.usage.index`
- `admin.reports.index`
- `admin.support.index`
- `admin.support.update`
- `admin.settings.index`
- `admin.settings.update`
- `admin.plans.index`
- `admin.plans.create`
- `admin.plans.store`
- `admin.plans.edit`
- `admin.plans.update`
- `admin.plans.toggle`

### F16.1 Admin dashboard

Show:

- MRR;
- expired businesses;
- over-limit businesses;
- additional usage revenue;
- user count;
- important operational alerts.

### F16.2 Businesses

List:
- business;
- owner;
- status;
- subscription summary;
- relevant usage.

Actions:
- open;
- suspend/activate;
- modules.

### F16.3 Business modules

Allow:
- inspect enabled modules;
- update module availability according to platform rules.

### F16.4 Users

Show:
- user;
- tenant/membership;
- role/status;
- platform admin status.

Actions:
- membership toggle;
- platform admin toggle where authorized.

### F16.5 Subscriptions

Show:
- business;
- plan;
- status;
- dates;
- payment state.

Action:
- suspend/activate where supported.

### F16.6 Platform payments

Show:
- payment;
- tenant/business;
- provider;
- amount;
- status;
- date/reference.

### F16.7 Usage

Show:
- tenant/business;
- usage period;
- included usage;
- extra/over-limit values;
- charges where returned.

### F16.8 Plans

Screens:
- plan list;
- create;
- edit;
- activate/deactivate.

### F16.9 Support

Show:
- tickets;
- tenant;
- status;
- priority/content as returned.

Allow supported status updates.

### F16.10 Platform settings

Show only currently supported settings.

### F16.11 Admin safety

For destructive/financial/platform actions:

- explicit scope;
- confirmation;
- visible resulting state;
- audit-friendly feedback.

---

## 21. System/error screens — Phase F17

Create consistent screens/states for:

- 403 Forbidden;
- 404 Not Found;
- 419 Session expired;
- 429 Too Many Requests;
- 500 Server Error;
- maintenance/unavailable state where required.

Prefer contextual recovery:
- back;
- dashboard;
- retry;
- relevant list.

---

## 22. Shared component library

Create reusable Blade components before duplicating markup.

### Navigation
- app sidebar
- admin sidebar
- mobile drawer
- topbar
- breadcrumbs

### Actions
- button primary
- button secondary
- button quiet
- button danger
- icon button

### Forms
- input
- textarea
- select
- date input
- time input
- checkbox
- switch
- radio/card option
- field error
- help text
- form section

### Data
- card
- stat
- table
- mobile record card
- pagination
- badge
- avatar
- empty state
- skeleton

### Feedback
- toast
- alert
- confirmation modal
- success panel
- error panel

### Booking-specific
- booking status badge
- payment status badge
- service option
- staff option
- date selector
- availability slot
- booking summary

### Billing-specific
- plan card
- usage meter
- subscription status
- billing event/transaction row

---

## 23. Permission and module UI rules

The frontend may conditionally render navigation/actions based on known permissions/modules.

Examples:

- Services requires services module + permission.
- Staff requires staff module + permission.
- Calendar/scheduling requires calendar module + permission.
- Customers requires customers module + permission.
- Payments requires payments module + billing permission.
- Subscription actions require subscription management permission.
- Admin screens require platform access.

However:

> Hidden UI is only a UX improvement. The backend remains the authorization source of truth.

When the backend returns 403/module-disabled/subscription-denied:

- show a clear state;
- preserve valid page context;
- provide the appropriate next action when possible.

---

## 24. Localization

Every feature must support:

- English;
- Arabic.

Avoid hard-coded English strings in reusable UI.

### Arabic requirements

- natural Arabic wording;
- RTL layout;
- logical padding/margin;
- proper icon direction where applicable;
- tables readable in RTL;
- form errors aligned correctly;
- dates/times/currency remain legible;
- mixed Arabic/English values do not break layout.

---

## 25. Theme

Support:

- Light;
- Dark.

Dark mode must use layered surfaces rather than simply making everything black.

Check:

- cards;
- forms;
- tables;
- badges;
- modals;
- calendar;
- public booking;
- logo visibility;
- empty/error/success states.

Theme switching must not move layout.

---

## 26. Responsive targets

Every major screen is validated at:

- 1440px;
- 1280px;
- 1024px;
- 768px;
- 390px;
- 360px.

At mobile:

- no accidental horizontal scrolling;
- actions remain reachable;
- form controls remain tappable;
- time slots are easy to select;
- tables have deliberate mobile representations;
- important information comes before decorative content.

---

## 27. Accessibility

Minimum bar:

- semantic HTML;
- visible focus;
- keyboard navigation;
- labels for all inputs;
- associated errors;
- sufficient contrast;
- meaningful button names;
- icon-only buttons have accessible labels;
- status is not communicated by color alone;
- touch targets are practical on mobile.

---

## 28. Performance rules

Keep the frontend lightweight.

Rules:

- Blade SSR first;
- minimal JS;
- no unnecessary SPA state;
- no oversized component libraries;
- paginate operational lists;
- avoid rendering huge datasets in one page;
- lazy-load non-critical images;
- avoid repeated client-side fetching when server rendering already provides data;
- keep public booking especially lightweight.

---

## 29. Screen completion checklist

A screen is **not complete** just because its route works.

### Structure
- [ ] correct hierarchy
- [ ] primary action is obvious
- [ ] no redundant sections

### Data
- [ ] real backend data
- [ ] correct formatting
- [ ] correct empty handling

### States
- [ ] loading
- [ ] empty
- [ ] validation error
- [ ] server error
- [ ] success
- [ ] forbidden/module-disabled where relevant

### Responsive
- [ ] 1440
- [ ] 1024
- [ ] 768
- [ ] 390
- [ ] 360

### Localization
- [ ] English
- [ ] Arabic
- [ ] LTR
- [ ] RTL

### Theme
- [ ] light
- [ ] dark

### Accessibility
- [ ] keyboard
- [ ] labels
- [ ] focus
- [ ] contrast
- [ ] status not color-only

### Performance
- [ ] no unnecessary JS
- [ ] no unnecessary dependency
- [ ] optimized assets
- [ ] sane query/render size

---

## 30. Frontend implementation order

Implementation order is fixed unless a dependency requires a small adjustment:

| Phase | Area | Priority |
|---|---|---|
| F0 | Design tokens + shared components | Foundation |
| F1 | Authentication | 1 |
| F2 | Onboarding | 2 |
| F3 | Dashboard | 3 |
| F4 | Business/settings | 4 |
| F5 | Services | 5 |
| F6 | Staff | 6 |
| F7 | Scheduling | 7 |
| F8 | Bookings | 8 |
| F9 | Calendar | 9 |
| F10 | Customers | 10 |
| F11 | Payments | 11 |
| F12 | Public booking | 12 |
| F13 | Billing/subscriptions | 13 |
| F14 | Notifications | 14 |
| F15 | Reports | 15 |
| F16 | Platform Admin | 16 |
| F17 | System/error screens + final polish | 17 |

---

## 31. Definition of Done for the complete frontend

The frontend is complete when:

- all required MVP screens are implemented;
- all current backend routes have an appropriate UI;
- tenant and role restrictions are respected;
- modules/subscriptions affect UI correctly;
- public booking works end-to-end;
- booking/reschedule/calendar flows are usable;
- payment states are correctly represented;
- subscription lifecycle is understandable;
- Arabic/English are complete;
- RTL/LTR are complete;
- light/dark are complete;
- mobile QA is complete;
- accessibility basics are complete;
- production build passes;
- no unnecessary frontend dependency was added;
- no known screen is left with placeholder content;
- the final QA sweep covers auth, onboarding, dashboard, bookings, public booking, billing and admin.

---

## 32. Source-of-truth relationships

Frontend implementation must remain aligned with:

- `DOC/00-PROJECT-SPECIFICATION.md`
- `DOC/01-PRODUCT-REQUIREMENTS.md`
- `DOC/02-ARCHITECTURE.md`
- `DOC/04-TENANCY-RBAC.md`
- `DOC/05-BOOKING-AVAILABILITY.md`
- `DOC/06-PAYMENTS.md`
- `DOC/07-SUBSCRIPTIONS-USAGE-BILLING.md`
- `DOC/08-LOCALIZATION-THEMING-SEO.md`
- `DOC/09-PERFORMANCE.md`
- `DOC/10-SECURITY.md`
- `DOC/13-TESTING-QA.md`
- `DOC/15-IMPLEMENTATION-CHECKLIST.md`
- `DOC/16-NOTIFICATIONS.md`
- `DOC/17-PRODUCTION-READINESS.md`
- `DOC/18-FRONTEND-DESIGN-IMPLEMENTATION.md`
- `DOC/20-API-STRUCTURE.md`

If a frontend idea conflicts with these documents or with the current backend behavior, update the design/implementation to match the actual product contract rather than inventing a parallel flow.

---

## 33. Final frontend rule

> **BookResa should feel clear first, fast second, beautiful third — and never beautiful at the expense of clear or fast.**
