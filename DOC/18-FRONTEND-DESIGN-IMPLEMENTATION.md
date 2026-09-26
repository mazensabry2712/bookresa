# BookResa — Frontend Design & Implementation Guide

> This document is the working source of truth for the BookResa frontend.
> It defines the visual language, UX rules, reference products, implementation order, and quality bar before we continue frontend work.

## 1. Frontend objective

BookResa should feel like a real product built for small businesses, not a generic SaaS template.

The interface needs to be:

- calm and professional
- fast to understand
- fast to operate
- mobile-friendly
- fully usable in Arabic and English
- comfortable in light and dark mode
- consistent across the whole product
- visually connected to the BookResa brand
- simple enough for a receptionist to use without training

The product is not supposed to win through decoration. It should win through clarity, useful defaults, good empty/loading/error states, and a booking flow that removes unnecessary work.

## 2. Brand direction from the approved visual reference

### Core colors

| Token | Value | Use |
|---|---|---|
| brand.navy | #1E2A44 | primary brand, sidebar/header accents, strong text |
| brand.indigo | #6366F1 | primary action, links, selected states, active navigation |
| brand.coral | #FF7A66 | small highlights, attention, calendar mark/icon detail |
| brand.soft | #EDEFF6 | soft surfaces, muted backgrounds, subtle borders |
| brand.slate | #4B5563 | secondary text, metadata |
| white | #FFFFFF | main light surfaces |

Rules:

1. Navy and indigo carry the product.
2. Coral is an accent, not a second primary color.
3. Indigo should not become a giant gradient everywhere.
4. Most screens should remain neutral; color is used to communicate hierarchy and state.
5. Dark mode gets its own surface values while keeping the same brand identity.
6. Marketing and logo treatments may be more expressive than the application UI.

### Typography

The brand reference uses Plus Jakarta Sans.

Use it as the main product typeface when the font is available through the agreed asset strategy. Arabic fallback must remain readable and balanced.

Hierarchy:

- Page title: strong, compact, not oversized.
- Section title: clear and practical.
- Body: comfortable reading size.
- Metadata/helper text: smaller and visually quieter.
- Numbers: highly legible, especially prices, booking counts, and times.

Do not use many font weights on one screen. A small, repeatable type scale is better than decorative typography.

### Logo

The core concept is a calendar shape combined with the letter B.

Approved practical usage:

- full wordmark on desktop sidebar/header
- compact icon on mobile
- icon-only variant for favicon/app icon
- light version on dark backgrounds

Do not redraw the mark inside application components.

## 3. Overall visual style

### What the product should feel like

Think:

clean operations software + friendly booking product + understated brand

The UI should have enough character to feel owned by BookResa, but not enough decoration to distract from the work.

### What we deliberately avoid

- excessive gradients
- glassmorphism
- floating cards everywhere
- huge rounded rectangles
- oversized hero text inside the dashboard
- decorative blobs and random abstract shapes
- excessive shadows
- animation for the sake of animation
- ten different button styles
- inconsistent icon families
- fake-looking dashboard numbers
- giant illustrations inside operational screens
- generic motivational copy
- lorem ipsum in final screens
- UI that looks like a generated template with every element centered inside a card

A good rule:

> Every visual element must either help the user understand, decide, or act.

## 4. Reference products: what we study

We do not clone these products. We study their interaction patterns, information hierarchy, booking flows, and operational conventions, then build the BookResa version around our own product model.

### 4.1 Calendly

Reference:
- https://calendly.com/
- https://calendly.com/scheduling/embed-options
- https://calendly.com/help/how-to-create-a-routing-form

Study:
- scheduling flow
- simple event/service selection
- routing and qualification ideas
- embedded booking behavior
- compact information hierarchy

BookResa translation:
- keep the booking journey focused
- prefill or skip choices when there is only one valid option
- use clear next actions
- preserve the customer's selections when moving between steps

### 4.2 Acuity Scheduling

Reference:
- https://acuityscheduling.com/
- https://help.acuityscheduling.com/hc/en-us/articles/16676869573645-How-clients-book-appointments
- https://help.acuityscheduling.com/hc/en-us/articles/31039700427277-Customize-your-scheduling-page
- https://help.acuityscheduling.com/hc/en-us/articles/16676894249101-Elements-of-a-scheduler

Study:
- appointment/service selection
- calendar and time-slot presentation
- different scheduler layouts
- branded scheduling page structure
- how appointment information is exposed without overload

BookResa translation:
- choose the simplest calendar presentation for the current booking scenario
- make duration, price, staff, and policy information easy to scan
- support a branded public booking page without turning it into a page builder

### 4.3 Setmore

Reference:
- https://www.setmore.com/online-scheduling
- https://support.setmore.com/en/collections/87070-booking-page
- https://support.setmore.com/en/articles/8925889-customize-your-booking-page

Study:
- customer-facing booking page
- business branding controls
- booking rules and lead times
- shareable booking URL
- service/staff/availability presentation

BookResa translation:
- public booking page should immediately explain the business, services, and next step
- booking rules should be visible at the correct moment
- business owners get useful branding controls without needing a website builder

### 4.4 Square Appointments

Reference:
- https://squareup.com/help/us/en/article/5349-schedule-and-accept-appointments

Study:
- operational scheduling
- service/staff setup relationship
- quick appointment creation
- business-first dashboard thinking

BookResa translation:
- optimize dashboard actions around real daily work
- make booking status, staff, customer, payment, and time visible together
- keep frequent operations one or two interactions away

### 4.5 SimplyBook.me

Reference:
- https://simplybook.me/en/booking-system-features-and-integrations
- https://simplybook.me/en/booking-system-features

Study:
- breadth of booking functionality
- business/client management
- booking website
- reminders
- service provider relationships
- flexible business rules

BookResa translation:
- keep the core platform generic
- expose useful options through modules/settings
- do not force every business into one fixed workflow

### 4.6 Secondary visual references

For general SaaS quality, we can also inspect:

- Stripe Dashboard — billing tables, status handling, financial hierarchy
- Linear — information density and clean navigation
- Notion — settings and content organization

These are secondary references. BookResa remains a booking product first.

## 5. How we study a reference without copying it

For every screen we inspect, use this process:

1. Observe
   - What is the first thing the user sees?
   - What is the primary action?
   - What information is visible before scrolling?
   - What is hidden until needed?

2. Decompose
   - navigation
   - header
   - page title
   - filters
   - primary action
   - data area
   - empty state
   - feedback
   - responsive behavior

3. Translate
   - replace their terminology with BookResa terminology
   - replace their data model with our tenant/booking/service/staff model
   - keep only interaction patterns that solve a real BookResa problem

4. Improve
   - remove unnecessary steps
   - improve mobile behavior
   - make Arabic RTL behave naturally
   - add missing loading/empty/error states
   - make permissions visible in the right places
   - keep common actions closer to the user

5. Validate
   - desktop 1440px
   - laptop/tablet around 1024px
   - mobile around 390px
   - Arabic RTL
   - English LTR
   - light mode
   - dark mode

The target is not visual similarity. The target is a better product experience built from proven interaction ideas.

## 6. Application shell

### Desktop

Primary layout:

- left sidebar
- top/header area
- main content
- optional contextual actions inside the page header

Sidebar order should reflect daily work:

1. Dashboard
2. Bookings
3. Calendar
4. Services
5. Staff
6. Customers
7. Payments
8. Subscription
9. Settings

Admin has a separate shell and must remain visually distinct from tenant operations.

### Sidebar rules

- compact and readable
- active item is obvious
- icons are supporting signals, not the only label
- grouped navigation only when grouping genuinely helps
- no excessive nested menus
- business/workspace identity visible without taking most of the sidebar

### Mobile

Do not simply squeeze the desktop sidebar.

Use:

- compact header
- menu drawer
- large touch targets
- page-level action button where appropriate
- horizontally scrollable tabs only when necessary
- tables converted to cards or focused rows when a full table is not usable

## 7. Dashboard direction

The dashboard should answer three questions immediately:

1. What is happening today?
2. What needs attention?
3. What should I do next?

Recommended structure:

### Header

- greeting / business context
- current date or useful range
- primary action such as New booking

### Summary

Use a small number of meaningful metrics:

- today's bookings
- today's revenue
- upcoming bookings
- new customers

Avoid ten KPI cards.

### Main work area

Show upcoming/recent bookings with:

- time
- service
- customer
- staff
- booking status
- payment state when relevant

Provide a clear View all action.

### Alerts / attention area

Only show when there is something useful:

- pending approval
- unpaid booking
- expiring subscription
- usage threshold warning
- failed payment

Never create an alert card just to fill space.

## 8. Booking management UI

### Bookings list

Primary controls:

- date/range
- status
- staff
- service
- customer search

Row/card information:

- booking reference
- customer
- service
- staff
- date/time
- status
- payment status

Actions:

- open details
- change status when allowed
- reschedule
- cancel when allowed
- payment action when relevant

Avoid action-button soup. Use one visible primary action and a compact secondary menu for uncommon actions.

### Booking details

The detail screen should read like a small operational record:

- booking status at the top
- customer
- service
- staff
- date/time
- price/payment
- notes
- timeline/history where useful
- allowed next actions

Destructive actions require confirmation and should explain the consequence.

## 9. Calendar UI

The calendar is a core tool, not decoration.

Requirements:

- day/week views where supported by the current backend
- obvious current day
- staff/resource filtering
- clear booking blocks
- collision/overlap readability
- quick create from an open slot
- visual difference between confirmed, pending, cancelled and completed
- readable on smaller screens

Do not rely on color alone for status. Use text, icon, or shape differences too.

## 10. Services UI

Services are business inventory.

List:

- service name
- duration
- price
- assigned staff count
- active/inactive state

Create/edit form:

1. basic information
2. duration and price
3. booking behavior
4. assigned staff
5. optional business-specific details

Do not show every optional field at once when it is not needed.

## 11. Staff UI

Staff list:

- name
- role
- services
- schedule state
- active/inactive

Staff details:

- profile
- assigned services
- working schedule
- blocked/unavailable periods
- booking-related information

Scheduling UI should make the difference between recurring working hours and one-off blocked periods obvious.

## 12. Customers UI

Customers are operational records, not CRM theater.

List:

- name
- contact
- last booking
- total bookings
- status when relevant

Profile:

- contact details
- booking history
- payment history where allowed
- useful notes

Avoid adding unnecessary charts or scoring systems.

## 13. Public booking page

This is one of the most important screens in BookResa.

The page should feel more focused than the admin dashboard.

Recommended structure:

### Step 1 — Business identity

- logo/icon
- business name
- short description or instruction
- language/theme controls when needed

### Step 2 — Service

Show only what the customer needs:

- service name
- short description
- duration
- price

### Step 3 — Staff

Show staff only when staff choice is relevant.

Rules:

- one valid staff member → skip the choice
- many valid staff members → show simple selection
- no customer need to understand internal scheduling logic

### Step 4 — Date and time

This is the main decision screen.

Priorities:

- available dates
- available times
- timezone clarity
- loading feedback
- unavailable days/slots should be obvious

Avoid forcing the customer through large empty calendar areas.

### Step 5 — Customer details

Ask only for required information.

Group fields logically.

Errors appear next to the relevant field and do not destroy previously entered information.

### Step 6 — Payment

When payment is required:

- show total clearly
- show what is being paid for
- explain any deposit/fee
- never hide the final amount

### Step 7 — Confirmation

The success screen should be useful enough to act as a receipt:

- booking reference
- service
- date/time
- staff when relevant
- payment status
- business information
- next action
- clear reschedule/cancel path when supported

## 14. Onboarding

Onboarding should get a new business from signup to a usable booking page quickly.

Recommended sequence:

1. create business/workspace
2. choose business type
3. business basics
4. timezone
5. add first service
6. add first staff member
7. configure working hours
8. preview booking page
9. finish and open dashboard

Rules:

- one decision per screen where possible
- smart defaults
- clear progress
- allow skipping non-essential setup
- never ask for information the backend does not currently use
- always show a useful next step

## 15. Settings

Settings should be grouped by responsibility instead of one enormous page.

Suggested groups:

### Business

- business profile
- logo
- contact details
- timezone
- business type

### Booking

- booking rules
- cancellation/reschedule policy
- lead time
- availability behavior

### Appearance

- logo/brand presentation
- theme
- language

### Team

- members
- roles/permissions

### Billing

- plan
- usage
- payment history
- subscription actions

## 16. Admin panel

The Platform Admin is not a dressed-up tenant dashboard.

It should prioritize:

- tenants/businesses
- subscriptions
- plans/modules
- platform payments
- activity/audit information
- operational support actions

Admin UI should be denser than tenant UI when necessary, but still readable.

Never expose a platform-level destructive action without clear scope and confirmation.

## 17. Component rules

We build a small internal visual system instead of installing a large UI kit.

### Buttons

Keep a small set:

- primary
- secondary
- soft/quiet
- danger
- icon-only

Every button needs a clear action label.

### Inputs

Rules:

- visible labels
- consistent height
- clear focus state
- clear error state
- helpful placeholder only when useful
- no placeholder-as-label

### Cards

Cards are for grouping related information, not for every block.

Use flat sections when a card would add no meaning.

### Tables

Use tables for operational records.

Requirements:

- readable density
- consistent columns
- pagination
- empty state
- loading state
- responsive fallback

### Status badges

Use stable semantic labels:

- pending
- confirmed
- completed
- cancelled
- failed
- paid/unpaid

Do not invent many colors. State meaning should stay consistent across the product.

### Modals

Use a modal for:

- short confirmations
- destructive confirmations
- quick small actions

Use a full page for:

- complex forms
- long settings
- detailed records

### Toasts

Use toasts for lightweight feedback.

Do not put important validation errors only in a toast.

## 18. Loading, empty, error, and success states

Every important screen must define four states.

### Loading

Prefer:

- small skeletons
- preserved layout
- no giant spinner in the middle of the page unless the whole page genuinely blocks

### Empty

An empty state should explain:

- what is empty
- why it matters
- the next action

Example:

No services yet
Add your first service so customers can start booking.

Then one clear action.

### Error

Show:

- what failed
- whether the user can retry
- whether their data was preserved

Never replace the whole interface with a generic Something went wrong screen when the actual problem is local.

### Success

Success should explain what happened and what to do next.

## 19. Localization and RTL

Arabic is not a translated-afterthought version of the English UI.

Requirements:

- dir="rtl" is treated as a layout mode
- logical spacing and alignment, not hard-coded left/right assumptions
- icons that imply direction must flip when appropriate
- calendars and date inputs remain understandable
- tables remain readable in RTL
- numbers and currency stay legible
- mixed Arabic/English content must not break layout
- labels should be written naturally in Arabic, not mechanically translated

Every important screen must be checked in:

- English / LTR
- Arabic / RTL

## 20. Light and dark mode

Dark mode is a real theme, not a color inversion.

Rules:

- maintain contrast
- use layered surfaces
- do not make every surface pure black
- keep borders subtle but visible
- preserve semantic colors
- keep the logo readable
- do not use bright purple/coral as large background surfaces

Theme switching must not cause layout movement.

## 21. Responsive quality bar

Target widths for QA:

- 1440px desktop
- 1280px laptop
- 1024px tablet/small laptop
- 768px tablet
- 390px mobile
- 360px compact mobile

At mobile width:

- no horizontal scrolling for standard screens
- primary actions remain reachable
- forms remain readable
- booking times are tappable
- important information appears before decorative content
- tables have a deliberate mobile representation

## 22. Accessibility

Minimum rules:

- semantic HTML
- keyboard navigation
- visible focus states
- sufficient text/background contrast
- form labels
- error association
- button names that make sense without the icon
- touch targets large enough for practical use
- do not encode meaning using color alone

Accessibility is especially important in operational areas where users spend many hours per day.

## 23. Performance rules

Frontend performance is part of the product.

Current architecture remains:

- Blade SSR
- Tailwind CSS
- Vite
- small amounts of JavaScript
- no SPA rewrite
- minimal dependencies

Rules:

- do not add a frontend framework just to build one interactive screen
- do not install a component library unless there is a concrete repeated need
- avoid large client-side charting packages for simple metrics
- keep critical pages server-rendered
- defer non-critical JavaScript
- avoid loading assets that are not visible or required
- optimize images
- avoid unnecessary re-rendering and polling
- keep the public booking page especially lightweight

Before adding a dependency, ask:

1. Can Blade/Tailwind do it?
2. Can a small native JS module do it?
3. Is the dependency solving a repeated product need?
4. Will it improve maintainability enough to justify the cost?

## 24. How BookResa differentiates through UI/UX

The goal is not to out-decorate other products. Differentiation comes from product decisions.

### 24.1 Faster booking

Remove unnecessary choices.

Example:
- one service → preselect it
- one staff member → skip staff selection
- one location → do not ask the user to choose
- returning customer → reuse known information where appropriate

### 24.2 Operational clarity

A receptionist should understand a booking record in seconds.

Show the important pieces together:
- who
- what
- when
- with whom
- status
- payment

### 24.3 Strong defaults

The product should work well before a business owner customizes everything.

### 24.4 Arabic-first quality

RTL should feel designed, not mirrored.

### 24.5 Real states

Most weak dashboards look fine in the happy path and fail in:

- no data
- delayed data
- failed actions
- permission restrictions
- long text
- mobile
- RTL

BookResa treats those states as part of the design.

### 24.6 Calm visual identity

The navy/indigo/coral combination gives the product enough personality without forcing bright colors into every component.

### 24.7 Useful density

The dashboard should fit real work on the screen without becoming visually noisy.

## 25. Human-made visual quality rules

The product should look intentionally designed by a product team.

That means:

- consistent spacing, but not mechanically identical blocks
- typography chosen around actual content
- real labels and realistic records during development
- purposeful empty space
- small visual imperfections that come from real information hierarchy, not random decoration
- fewer components, reused well
- copy written for the user's task
- visual emphasis used only where the product needs it

Do not manufacture a fake sense of sophistication.

A professional screen can be simple.

## 26. Copywriting rules

UI copy should be:

- direct
- specific
- human
- short
- action-oriented

Prefer:
- New booking
- Add service
- Invite staff
- Copy booking link
- Payment pending

Avoid:
- Unlock the future of your business
- Take your business to the next level
- Revolutionize your workflow

The product voice should sound like a competent software product, not an advertisement on every screen.

## 27. Iconography

Use one consistent icon family.

Rules:

- simple outline icons for navigation
- solid emphasis only when the UI needs stronger hierarchy
- no mixing unrelated icon styles
- icon size must follow a small fixed scale
- icon color should normally follow semantic text/action color

Never use an icon just because an empty space exists.

## 28. Motion

Motion is functional.

Use it for:

- opening/closing drawers
- modal transitions
- state changes
- small feedback

Avoid:

- constant floating elements
- long entrance animations
- parallax
- animations that delay a task

The interface should still feel good with reduced motion enabled.

## 29. Implementation architecture

Keep the current Blade-first architecture.

Recommended organization:

- resources/views/layouts/ — page shells
- resources/views/components/ — small reusable UI pieces
- module-specific views — domain screens
- resources/css/app.css — global design tokens/utilities
- resources/js/ — small interaction modules only

Do not create a giant global component system before repeated patterns actually appear.

A component should become shared when:

- it is used in multiple places, and
- the markup/behavior is meaningfully the same

## 30. Frontend implementation sequence

The frontend rollout follows the same product dependency graph used by the backend. The exact screen order below is the execution contract.

### F0 — Design foundation + shared shell
Build:
- BookResa design tokens
- typography and spacing
- buttons, inputs, badges, alerts
- tables, pagination, cards
- loading/empty/error/success states
- light/dark mode
- RTL/LTR-safe primitives
- tenant application shell
- Platform Admin shell
- public booking shell
- mobile navigation

Exit condition:
All future screens can use shared components and shells without duplicated visual foundations.

### F1 — Authentication
Screens:
- Login
- Register
- Email verification
- Forgot password
- Reset password

Exit condition:
The complete authentication flow is usable in Arabic/English, RTL/LTR and light/dark.

### F2 — Business onboarding
Screens:
- Create Business
- Business Type
- Workspace
- Modules
- Services setup
- Working Hours setup
- Staff setup
- Workspace Ready

Exit condition:
A verified user can create a Business, configure required workspace data and finish onboarding.

### F3 — Dashboard
Screens:
- Main Dashboard
- Upcoming Bookings
- Attention/Alerts
- Subscription/Usage summary

Exit condition:
The business owner can understand today's workload and the next useful action immediately.

### F4 — Business and settings
Screens:
- Business Profile
- Logo/Cover
- Booking Settings
- Appearance
- Language
- Theme
- Team/roles where exposed

Exit condition:
The owner can configure the business behavior supported by the current backend.

### F5 — Services
Screens:
- Services List
- Create Service
- Edit Service
- Delete Confirmation
- Empty State

Exit condition:
Services can be fully managed without leaving the business workspace.

### F6 — Staff
Screens:
- Staff List
- Add Staff
- Edit Staff
- Staff Status
- Staff Details

Exit condition:
Staff, service assignments and staff availability setup are understandable.

### F7 — Scheduling and availability
Screens:
- Scheduling Overview
- Business Hours
- Breaks
- Holidays
- Special Hours
- Staff Hours
- Days Off
- Staff Availability

Exit condition:
A business can configure recurring and exceptional availability without needing to understand internal slot-calculation logic.

### F8 — Bookings
Screens:
- Booking List
- Create Booking
- Booking Details
- Status Actions
- Reschedule
- Cancel Confirmation

Exit condition:
Daily booking operations, lifecycle actions and validation states are complete.

### F9 — Calendar
Screens:
- Day
- Week
- Month

Interactions:
- navigate dates
- filter staff where supported
- open booking details
- start a valid create flow from an available slot where supported

Exit condition:
Calendar is operational, readable and consistent with booking status/payment status.

### F10 — Customers
Screens:
- Customers List
- Add Customer
- Customer Details
- Booking History
- Payment History where permitted
- Usage/limit state

Exit condition:
Customer records and usage information are understandable and actionable.

### F11 — Payments
Screens:
- Payments History
- Booking Payment State
- Checkout/Pending/Failure/Success states
- Retry path where supported

Exit condition:
The UI clearly separates booking status from payment status and trusts server-side payment state.

### F12 — Public booking
Canonical routes:
- `/{tenant:slug}/book`
- `/{tenant:slug}/book/availability`
- `POST /{tenant:slug}/book`
- signed confirmation route

Screens/steps:
1. Business identity
2. Service
3. Staff when relevant
4. Date
5. Available time
6. Customer information
7. Payment according to booking policy
8. Confirmation

Rules:
- phone is required by the current booking contract;
- email follows Business settings;
- customer account creation is not required;
- Full Payment, Deposit and Pay Later are supported by the current booking payment model;
- the canonical public booking route is the only new frontend link target; legacy public routes remain compatibility routes.

Exit condition:
A first-time customer can complete a booking comfortably from a mobile device.

### F13 — Billing and subscriptions
Screens:
- Plans
- Current Subscription
- Usage
- Billing/Payment History
- Upgrade/Downgrade
- Cancel
- Reactivate
- Renew

Exit condition:
The owner can understand plan, period, payment state, usage and lifecycle actions.

### F14 — Notifications
Build:
- global notification trigger/list where exposed;
- unread/read presentation;
- booking notifications;
- payment notifications;
- subscription expiry;
- usage threshold/limit/over-limit states.

Exit condition:
Important backend notifications have an understandable UI destination.

### F15 — Reports
Screens:
- Business Reports
- Platform Reports

Priority:
- useful summary data first;
- tables before decorative charts;
- clear date/range filters when supported.

Exit condition:
Business and platform operators can read the current report data without spreadsheet-style overload.

### F16 — Platform Admin
Screens:
- Admin Dashboard
- Businesses
- Business Modules
- Users
- Subscriptions
- Payments
- Usage
- Reports
- Plans
- Support
- Settings

Exit condition:
Platform operations are possible without using tenant-facing screens.

### F17 — System states + final QA
Build/review:
- 403
- 404
- 419
- 429
- 500
- maintenance/unavailable state where needed
- final responsive review
- Arabic/English review
- RTL/LTR review
- light/dark review
- keyboard/accessibility review
- performance review

Exit condition:
All MVP screens meet the Definition of Done in this document.

### Route contract

The browser frontend uses the existing named web routes in `routes/web.php`.

Tenant areas:
- `dashboard`
- `onboarding.*`
- `business.profile.*`
- `services.*`
- `staff.*`
- `scheduling.*`
- `booking.management.*`
- `calendar.index`
- `customers.*`
- `payments.index`
- `billing.*`
- `reports.business`

Public booking:
- `public.booking.canonical.*`

Platform Admin:
- `admin.*`

Future API work is documented by the architecture/API-readiness sections of the existing technical documents. The current browser frontend must remain Blade-first and must not be reimplemented around a client-side API.

### Backend contract rule

The frontend must reflect the server contract for:
- tenant isolation;
- roles and permissions;
- module availability;
- subscription usability;
- customer usage policy;
- booking lifecycle;
- availability;
- payment state;
- subscription lifecycle;
- notifications.

When backend behavior changes, update the relevant existing DOC first, then update this frontend document if the screen flow is affected.

## 31. Screen-by-screen implementation checklist

For every screen, complete this checklist before calling it finished:

### Structure
- [ ] correct layout
- [ ] correct hierarchy
- [ ] primary action is obvious
- [ ] no unnecessary sections

### Content
- [ ] real labels
- [ ] useful helper text
- [ ] realistic data examples
- [ ] no placeholder copy

### States
- [ ] loading
- [ ] empty
- [ ] error
- [ ] success
- [ ] permission-restricted state when relevant

### Responsive
- [ ] 1440px
- [ ] 1024px
- [ ] 768px
- [ ] 390px
- [ ] 360px

### Localization
- [ ] English
- [ ] Arabic
- [ ] LTR
- [ ] RTL
- [ ] mixed-language content checked

### Themes
- [ ] light
- [ ] dark

### Accessibility
- [ ] keyboard
- [ ] focus states
- [ ] labels
- [ ] contrast
- [ ] color-independent status

### Performance
- [ ] no unnecessary JS
- [ ] no unnecessary dependency
- [ ] assets optimized
- [ ] public pages remain lightweight

## 32. Visual review process

For important screens we will review in this order:

1. functional correctness
2. information hierarchy
3. spacing/alignment
4. typography
5. color
6. interaction states
7. responsive behavior
8. RTL
9. dark mode
10. performance

Do not spend time polishing shadows while the page hierarchy is still wrong.

## 33. Final quality bar

A frontend task is not finished just because:

- the route works
- the form submits
- the page looks okay on desktop

It is finished when the screen is useful in the real workflow.

The standard is:

> clear first, fast second, beautiful third — and never beautiful at the expense of clear or fast.

## 34. Sources / references

Official product references used for UX study:

- Calendly — https://calendly.com/
- Calendly embedded scheduling — https://calendly.com/scheduling/embed-options
- Calendly routing — https://calendly.com/scheduling/routing
- Acuity Scheduling — https://acuityscheduling.com/
- Acuity client booking flow — https://help.acuityscheduling.com/hc/en-us/articles/16676869573645-How-clients-book-appointments
- Acuity scheduler customization — https://help.acuityscheduling.com/hc/en-us/articles/31039700427277-Customize-your-scheduling-page
- Acuity scheduler elements — https://help.acuityscheduling.com/hc/en-us/articles/16676894249101-Elements-of-a-scheduler
- Setmore — https://www.setmore.com/online-scheduling
- Setmore booking page documentation — https://support.setmore.com/en/collections/87070-booking-page
- Setmore booking page customization — https://support.setmore.com/en/articles/8925889-customize-your-booking-page
- Square Appointments scheduling — https://squareup.com/help/us/en/article/5349-schedule-and-accept-appointments
- SimplyBook.me features — https://simplybook.me/en/booking-system-features-and-integrations
- SimplyBook.me system features — https://simplybook.me/en/booking-system-features

These references are inspiration for interaction and product patterns only. BookResa keeps its own brand, copy, information architecture, backend rules, and implementation.

## 35. Automated QA verification baseline

Latest verified frontend/application gate on 2026-09-26:

- PHP test suite: 241 passed, 1 skipped, 890 assertions
- Calendar feature tests: 4 passed, 13 assertions
- Vite production build: passed
- Blade view cache: passed
- GitHub Actions CI for commit `1cdb7443`: passed
- PHPStan, PHP syntax scan and changed-file Pint: passed in CI
- Mobile sidebar keyboard/accessibility behavior: verified by automated build/test gate
- RTL directional-arrow primitive: covered in Calendar feature tests

Manual browser review is still required for the visual/responsive checklist in section 31 across the target widths, locales and themes.
