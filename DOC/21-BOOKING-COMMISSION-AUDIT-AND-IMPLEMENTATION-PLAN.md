# BookResa — Booking Commission Audit and Implementation Plan

**Document:** DOC/21  
**Status:** Audit and plan only; the target model is not implemented yet  
**Audit baseline:** main at commit bd7243f502af6f834361a401ba5e75b1e5236770  
**Date:** 2026-10-20  
**Scope:** Monthly booking allowance, commissions, online and cash collection, workspace settlement, Super Admin, reporting, tests, and production rollout.

> Important: this document records repository findings and intended work. It does not claim the new monthly booking commission is implemented. At the audit baseline, eligibility is based on customer-profile ordinal, the default rate is 5%, and workspace payouts are manually recorded ledger entries. This documentation change must not enable production payments, change secrets, or run migrations.

## 1. Executive summary

The requested business model is:

- Each business/workspace has its own monthly free allowance, default 1,000 bookings.
- BookResa's default commission is 10% on eligible bookings after the first 1,000 counted bookings in that month. The first 1,000 do not incur BookResa commission.
- Super Admin controls the allowance, commission rate, and whether the commission policy is enabled.
- The allowance is per business and per calendar month. It is not based on unique customers and must not inherit the subscription plan's included-customer limit.
- Both cash and online bookings participate in the booking-count policy. Their funds paths differ: online money goes through the central merchant account in the current public flow; cash is collected directly by the business unless another approved payment arrangement is introduced.
- A computed commission, a commission actually collected by BookResa, a receivable owed by a business, a workspace amount payable, a reserved payout, and a payout actually sent are different financial states and must never be presented as interchangeable.

The current repository contains useful foundations: tenant isolation, booking and scheduling flows, provider-neutral PaymentGateway services, Kashier session/return/webhook handling, payment idempotency, a payment-linked commission ledger, a manual workspace-payout ledger, Super Admin settings, audit logging, reports, and feature tests. Extend these components in place rather than rebuilding the entire application.

## 2. Confirmed current state

### 2.1 Repository baseline

- Repository: mazensabry2712/bookresa.
- Source branch: main.
- Audited commit: bd7243f502af6f834361a401ba5e75b1e5236770.
- Stack: Laravel 13, PHP 8.4, Blade, Tailwind, Vite, MySQL, Fortify, Spatie Permission, Spatie Activitylog and Spatie Multitenancy.
- Main domains include tenant, booking, customer, service, staff, scheduling, payment, subscription/usage billing, modules, support, and platform administration.
- User-provided SSH output confirmed the server checkout was attached to main at this commit and that 51 focused payment tests passed with 243 assertions.
- That test result does not prove that a live/sandbox provider payment, refund, split settlement, or bank transfer has succeeded.
- The production checkout had untracked files: public/.htaccess.backup-20261019-034808, public/.htaccess.recovery-20261019-041204, public/build/assets/app-CqQKe-xL.js, and public/build/assets/app-DcirdSZo.css. Do not delete or overwrite these as routine cleanup; inspect them during deployment preflight.
- Hostinger PHP 8.4 CLI observed in the user-provided output is /opt/alt/php84/usr/bin/php. Do not use an incompatible default CLI version for Composer or Artisan.

### 2.2 Commission logic mismatch

Relevant files:

- app/Domain/Payment/Services/BookingCommissionPolicy.php
- app/Domain/Payment/Services/SyncBookingCommission.php
- app/Domain/Payment/Models/BookingCommission.php
- app/Http/Controllers/Platform/PlatformSettingsController.php
- resources/views/admin/settings/index.blade.php
- tests/Feature/BookingCommissionTest.php
- tests/Feature/PlatformSettingsTest.php

Observed behavior:

1. BookingCommissionPolicy counts Customer records within a tenant ordered by created_at and ID, and assigns a customer ordinal.
2. Eligibility compares that customer ordinal to an included limit. When a usable subscription exists, the code can use the subscription's included_customer_limit. Otherwise it uses the platform commission_free_customer_limit fallback.
3. The fallback is 1,000, but the current setting label is “Free customer profiles before commission”, not monthly bookings.
4. The commission rate currently defaults to 500 basis points (5%) in the policy, controller, view and tests.
5. StartBookingPayment snapshots commission policy when online checkout starts, before provider-confirmed payment. SyncBookingCommission later reconciles paid/refunded payment outcomes.
6. The commission row is unique by payment ID. This is insufficient as the sole unit of commission for a cash booking or a booking with multiple payment attempts.
7. The current behavior is not the required 1,000 bookings per month followed by 10% on subsequent eligible bookings.

### 2.3 Payments and settlement

- The current public online booking flow uses the platform's central Kashier merchant when centralized_booking_payments_enabled is enabled.
- A customer can pay cash at the business; that choice bypasses gateway checkout.
- Online booking checkout currently charges the full service-price snapshot. Deposit calculation and checkout lifecycle are not implemented.
- The payment core separates booking payments from business subscription payments. They must remain separate financial contexts.
- Kashier redirect and webhook processing have signature verification, server-side validation, amount/currency/merchant checks and idempotency controls. A browser redirect alone is not proof of payment.
- Real sandbox end-to-end verification remains a separate release gate. Actual webhook amount units and event/status behavior, especially capture, authorization, void, reversal and partial refunds, must be confirmed against known test transactions.
- Abandoned online checkout can leave a pending booking blocking availability; a dedicated expiry/release flow is a documented gap. A safe customer retry flow for expired/failed checkout also needs review.
- Live payment credentials and real customer charges were not used during this audit.

### 2.4 Existing payouts are not automatic transfers

Relevant files:

- app/Domain/Payment/Models/WorkspacePayout.php
- app/Http/Controllers/Platform/PlatformCommissionController.php
- resources/views/admin/commissions/index.blade.php
- tests/Feature/PlatformCommissionTest.php

The current platform screen reserves a workspace payout from a recorded balance. A finance operator executes an external bank/provider transfer separately, then records an external reference and marks that payout as paid. The application code does not itself send the transfer. This is a manual payout ledger, not automated marketplace split settlement.

A workspace net balance is not the same thing as an amount transferred. A commission ledger entry is not the same thing as money received by BookResa. These distinctions must remain visible in data, screens and reports.

### 2.5 Subscription usage is a separate domain

Relevant files include app/Domain/Billing/Services/CalculateSubscriptionUsage.php, app/Domain/Billing/Models/Subscription.php and the usage-period/charge models.

Subscription usage counts unique customer profiles. A customer with ten bookings remains one customer for subscription usage but may consume ten booking slots under the new commission allowance. Do not reuse or change included_customer_limit when implementing the monthly booking allowance.

Additional current-state finding: `CalculateSubscriptionUsage` computes the extra-customer count but currently sets `additionalCustomerPriceMinor` and `usageChargeMinor` to zero; the current plan-admin save path also stores `additional_customer_price_minor = 0`. Thus over-limit profiles are counted/displayed, but the runtime does not currently apply an additional-customer usage surcharge. Whether that separate product fee should be activated is distinct from the new booking commission and must not be silently merged into it.

## 3. Target business rules

### 3.1 Agreed defaults

| Rule | Target |
|---|---|
| Commission policy scope | Per business/workspace |
| Period | Calendar month, using a defined workspace timezone |
| Free allowance | 1,000 qualifying bookings per workspace/month |
| Commission rate | 10% by default, stored as 1,000 basis points |
| First 1,000 | No BookResa booking commission |
| Bookings after allowance | Commission only for eligible booking amounts beyond the allowance |
| Super Admin | Can change allowance, rate and commission-enabled switch |
| Customer count | Not used for booking allowance eligibility |
| Subscription plan | Included customer limit stays independent |
| Currency | Calculate and report by currency; never silently sum different currencies |
| Audit | Preserve period, ordinal, rule version, rate, basis, amounts, and adjustment history |
| Duplicate safety | Retries, reschedules, duplicate callbacks and webhook replays must not double count |
| Historic records | Settings changes must not silently rewrite existing commission snapshots |

### 3.2 Recommended edge-case policy to make explicit

The phrase “1,000 bookings per month” requires a formal qualifying event. The implementation should use a single documented rule instead of scattered status assumptions. Recommended defaults:

1. Allocate a monthly slot once when the booking first reaches a defined qualifying state, recommended as business-confirmed/accepted. A draft or failed attempt that never becomes a valid booking should not consume a slot.
2. A cash booking participates in the count even though the funds are not received by BookResa. The count and the money-collection status are separate.
3. A confirmed booking is not counted a second time when rescheduled. Retain the original booking identity and monthly ordinal.
4. Record cancellation and no-show history. Do not silently decrement the counter; any allowance credit for cancellation must be an explicit policy.
5. A commission cannot be described as collected until payment/cash reconciliation proves collection. The payable/receivable treatment for confirmed but unpaid bookings must be explicit.
6. Refunds must reverse or adjust the appropriate commission based on net funds retained, with a defined recovery path if the company has already been paid out.
7. Determine month boundaries in the workspace's valid IANA timezone. Persist the period instants and timezone so calculations are reproducible.
8. A settings change must have a defined effective-time rule. Recommended: new policy applies prospectively to new qualifying bookings; each existing allocation keeps its snapshot.
9. Multiple online payment attempts for one booking must not create duplicate booking commission. Partial payment handling must specify whether commission accrues proportionally or after full settlement.
10. Define whether the fee base includes/excludes discounts, tax, tips, and provider fees before production activation. Use integer minor units and one documented rounding rule.
11. Failed/expired online sessions are not paid collections. Pending bookings that block a slot must be resolved safely and retryable.
12. Define a deterministic rule for a booking created in one month and rescheduled into another. Recommended: ordinal stays tied to the period in which it originally qualified; rescheduling does not create a new slot.

These defaults are recommendations for the specification to finalize. Where business terms differ, update this section and the relevant tests before coding.

## 4. End-to-end flow to build

### 4.1 Monthly count

1. Resolve the active tenant through existing middleware/context; never trust client-supplied tenant ID.
2. Determine business-local month boundaries and persist the period/timezone.
3. Allocate a single ordinal atomically when a booking qualifies.
4. If ordinal <= allowance, record no commission under the saved policy.
5. If ordinal > allowance and the policy is enabled, snapshot the commission rate and eligible basis.
6. Reuse the same allocation on retry, duplicate event, or reschedule.
7. Start a new allowance period in the next calendar month while retaining old history.

A plain count followed by adding one is not concurrency-safe. Use transaction locking and database unique constraints/sequence rows to prevent duplicate ordinals at the 1,000/1,001 boundary.

### 4.2 Online payment

1. Create a booking payment attempt idempotently.
2. Start hosted checkout; do not treat session creation as collection.
3. Accept the trusted, validated provider success/webhook outcome only after merchant, payment reference, amount and currency match.
4. Reconcile the booking payment and applicable commission once.
5. Keep gross value, refunded value, commission, company net and actual cash movements separate.
6. Do not accrue collected revenue on pending, failed or expired payment.
7. Support retries and partial payments without applying the full commission multiple times.

### 4.3 Cash payment

1. Create and count the booking under the same policy as online bookings.
2. Record a cash/offline collection or a dedicated reconciled cash record; do not fake a Kashier-paid event.
3. Store who confirmed it, when, amount, currency, booking, method/reference and audit reason for corrections.
4. Display commission as due/receivable if owed to BookResa but not yet collected.
5. Provide an approved method for the business to remit that commission or settle it periodically.
6. Mark commission collected only when the remittance is confirmed.
7. Ensure corrections, duplicate confirmations, cancellations and refunds are idempotent and auditable.

### 4.4 Financial state definitions

The UI and data model must distinguish:

- Booking gross: value in the immutable booking snapshot.
- Customer funds received: verified payment or reconciled cash.
- Refunded amount: valid cumulative refund amount.
- Commission earned: the contractual BookResa share calculated for eligible bookings.
- Commission collected: amount actually received by BookResa.
- Commission receivable: amount the business owes BookResa, including applicable cash-booking commissions.
- Workspace net payable: amount due to the company from centralized funds after commission, refunds and explicitly listed fees.
- Payout reserved: funds earmarked for a pending payout.
- Payout paid: external transfer actually executed and recorded with a reference.
- Adjustment/reversal: an auditable correction to a previously recorded balance.

### 4.5 Funds model/provider approval

Before live charges, confirm in writing with the payment provider:

- Whether a single platform merchant can accept money for multiple independent businesses.
- Whether true marketplace split settlement is available, or whether BookResa must collect centrally and make separate transfers.
- Business verification/KYB requirements and merchant-account ownership rules.
- Transfer API availability, eligibility, limits, fees, settlement times and failure/reversal behavior.
- Refund, chargeback/dispute, reserve, tax, invoicing and negative-balance responsibilities.
- Legal and contractual permission for BookResa to collect funds for other businesses.

Do not treat a tenant payment-account row or stored merchant ID as evidence that marketplace onboarding or split settlement is active. If approved split settlement is not available, a manual/periodic commission and workspace settlement process can be considered only after business/legal approval.

## 5. Super Admin and workspace UX

### 5.1 Super Admin policy settings

Provide:

- Commission policy enabled/disabled independently of customer online-payment switch.
- Free monthly booking allowance, default 1,000.
- Commission rate, default 10%.
- Effective-time/version behavior for settings changes.
- A clear explanation of qualifying booking count and fee basis.
- Proper numeric validation, integer basis points and cache invalidation.
- Secure changes with actor/time/action audit logging.

Do not conflate:
- commission switch,
- centralized booking-payment switch,
- subscription plan enablement,
- included-customer usage policy.

### 5.2 Super Admin monthly finance view

Show per workspace/month and per currency:

- Counted bookings, free allowance, used slots, remaining free slots, and excess eligible bookings.
- Relevant booking statuses and exclusions according to the confirmed policy.
- Gross eligible amount, online collections, reconciled cash, refunds and net collected amounts.
- Commission earned, commission collected, commission receivable, and workspace net payable.
- Payout reserved, paid, cancelled and failed states.
- Source payment/reference, settlement reference and recent audit events.
- Filtering by workspace, month, status and currency.

Never present different currencies as a single monetary total.

### 5.3 Workspace finance view

For only the current tenant, show:

- Counted bookings this month, free allowance, remaining allowance and over-limit count.
- Commission rate and earned amount.
- Online paid, reconciled cash, refunded, commission due and payout/settlement status.
- A clear statement separating “earned”, “due”, “collected”, “reserved” and “paid”.
- Monthly statement/history and transaction references relevant to the workspace.

Every query, export and action must remain tenant-scoped and authorized server-side.

## 6. Data model and migrations

The exact schema must be designed against the latest main before implementation. The logical requirements are:

### 6.1 Booking monthly allocation

Add a durable allocation/counter model or equivalent uniquely constrained implementation holding:

- tenant/workspace ID;
- booking ID;
- period start/end and timezone;
- unique ordinal per workspace/period;
- qualifying status and timestamp;
- policy version/snapshot;
- timestamps and audit/reversal context.

Use a unique key for one allocation per booking under the chosen policy and a unique key for each workspace/period ordinal. A database transaction/lock or sequence row must guarantee no duplicated ordinal under concurrent requests.

### 6.2 Commission ledger

The current commission row is payment-keyed. Review whether to evolve it or introduce a booking-centric immutable ledger with payment/cash allocations. Preserve legacy data.

Required data: tenant, booking, period, ordinal, allowance, policy snapshot/version, rate in basis points, eligible amount, refund and adjustment totals, commission, company net, currency, status, source collection/payment references, and ledger metadata.

One booking with multiple attempts/payments must not create duplicate commission. A single payment must not automatically imply one independent commission if that payment is only one part of a booking.

### 6.3 Cash, receivable and payout

Choose one reviewed model:
- extend a shared payment model with explicit offline/cash statuses and actor/reconciliation data; or
- introduce separate cash-collection records and reconcile to a shared finance ledger.

Do not choose by convenience alone; verify current reports, casts, gateway logic, morph relations, status enums and tenant scopes first.

The model must represent receivables owed to BookResa separately from the amount BookResa owes workspaces. Preserve external payout reference, execution time, actor, currency, amount, and status transitions. No payout can be marked paid without a real external transfer confirmation/reference.

### 6.4 Migration safety

- Prefer additive, backward-compatible migrations.
- Review foreign keys, unique/index definitions, delete behavior, tenant soft-deletion and MySQL compatibility.
- Plan reconciliation/backfill for old commission data instead of silently recalculating it under new policy.
- Back up production before migrations and verify the backup/restore process.
- Deploy compatible code/schema in a safe order. Do not run a production migration before reviewing it and confirming backup.
- Do not drop tables or financial history as a routine rollback.

## 7. Full work inventory

| # | Area | Existing state | Required work |
|---:|---|---|---|
| 1 | Commission policy | Customer-profile ordinal | Monthly qualifying-booking ordinal |
| 2 | Super Admin defaults | Fallback 1,000 customer profiles; 5% rate | 1,000 bookings/month; 10%; independent enable switch |
| 3 | Subscription boundary | Commission may use subscription customer limit | Decouple commission allowance from included customer count |
| 4 | Monthly counter | Not implemented in required form | Per-tenant/per-period atomic allocation with unique constraints |
| 5 | Booking state integration | Existing create/status/reschedule services | Consume one slot only once at qualifying lifecycle transition |
| 6 | Commission snapshot | Captured at checkout | Snapshot period, ordinal, rate, policy version and approved fee basis |
| 7 | Payment reconciliation | Sync by payment | Booking-level accounting across attempts/partials |
| 8 | Online success | Kashier return/webhook integration exists | Reconcile verified collections without duplicates |
| 9 | Cash collections | Cash bypasses online checkout | Auditable offline collection/reconciliation records |
| 10 | Refund/cancellation | Payment refund adjustments exist | Booking ledger reversals, cancellations, post-payout recovery |
| 11 | Commission receivable | Not a complete cash-commission collection model | Amount due, remittance and collection records |
| 12 | Workspace payout | Manual ledger | Preserve manual truth; automate only with approved provider capability |
| 13 | Super Admin UI | Old allowance label and commission table | Monthly counter, earned/collected/receivable/payable breakdown |
| 14 | Workspace UI | Booking/payment/billing screens exist | Monthly allowance meter and finance statement |
| 15 | Reports/dashboard | Booking revenue, subscription and usage metrics exist | Commission and settlement metrics without double counting |
| 16 | Pending checkout | Abandoned booking may block availability | Safe expiry, release, cancellation and retry flow |
| 17 | Permissions/audit | Tenant and platform controls exist | Secure cash, adjustments, receivables, exports and payout actions |
| 18 | Tenant isolation | Tenant context and scopes exist | Cross-tenant denial tests for every new resource/action |
| 19 | Database | Payment, commission and payout tables exist | Additive schema for monthly counter and required ledger links |
| 20 | Tests | Assertions reflect customer ordinal/5% | Update obsolete assertions and add policy/ledger edge cases |
| 21 | Provider integration | Mocked/automated Kashier tests exist | Real sandbox session, webhook, duplicate, refund and status verification |
| 22 | Production rollout | Main deployed in checkout per SSH output | Fresh preflight, backup, migrations, deploy, smoke tests and recovery |
| 23 | Documentation | Numbered DOC series exists | Sync core specification, payment, usage, security, QA and readiness docs |
| 24 | Marketplace/legal flow | Automatic split settlement not implemented | Provider confirmation and business/legal approval before live money movement |

## 8. Expected code touchpoints

Re-read all files from current main before coding; this list is a map, not a promise to change every listed file.

### Settings and policy
- app/Http/Controllers/Platform/PlatformSettingsController.php
- app/Support/PlatformSettings.php
- resources/views/admin/settings/index.blade.php
- new/updated booking-commission policy configuration/service
- translations for all changed labels and messages

### Booking and allocation
- app/Domain/Booking/Actions/CreateBooking.php
- app/Domain/Booking/Actions/UpdateBookingStatus.php
- app/Domain/Booking/Services/RescheduleBooking.php
- app/Domain/Booking/Models/Booking.php
- app/Http/Controllers/PublicBookingController.php
- any booking-management request/controller needed for a qualifying-state transition
- new monthly allocation model, service and migration

### Payment, cash and finance ledger
- app/Domain/Payment/Services/StartBookingPayment.php
- app/Domain/Payment/Services/PaymentService.php
- app/Domain/Payment/Services/SyncBookingCommission.php
- app/Domain/Payment/Services/HandleKashierWebhook.php
- app/Http/Controllers/Payment/KashierReturnController.php
- app/Http/Controllers/Payment/KashierWebhookController.php
- app/Http/Controllers/Platform/PlatformPaymentController.php
- app/Domain/Payment/Models/BookingCommission.php
- app/Domain/Payment/Models/WorkspacePayout.php
- new cash-collection/receivable ledger models only after design review

### Admin, workspace, reporting
- app/Http/Controllers/Platform/PlatformCommissionController.php
- resources/views/admin/commissions/index.blade.php
- app/Http/Controllers/Payment/PaymentManagementController.php
- app/Http/Controllers/Report/ReportController.php
- app/Http/Controllers/Platform/PlatformDashboardController.php
- booking confirmation/payment views and workspace billing/finance views as needed
- routes, permissions, navigation and translations

### Tests
- tests/Feature/BookingCommissionTest.php
- tests/Feature/PlatformCommissionTest.php
- tests/Feature/PlatformSettingsTest.php
- tests/Feature/PublicBookingPaymentTest.php
- tests/Feature/PaymentCoreTest.php
- tests/Feature/KashierGatewayTest.php
- tests/Feature/KashierReturnTest.php
- tests/Feature/KashierWebhookTest.php
- tests/Feature/BookingEngineTest.php
- tests/Feature/BookingManagementTest.php
- tests/Feature/ReportsTest.php
- new concurrency, cash reconciliation, receivable collection, and post-payout refund tests

## 9. Verification matrix

### Policy boundaries
- [ ] Allowance 1,000: bookings 1, 999 and 1,000 are not commission-eligible.
- [ ] Booking 1,001 is eligible if commission is enabled and all confirmed conditions apply.
- [ ] Defaults are exactly 1,000 bookings and 10%.
- [ ] Monthly counter is isolated per workspace and resets at the configured local month boundary.
- [ ] Changing subscription customer limits does not change commission allowance.
- [ ] Each booking retains its policy snapshot; no silent retroactive recalculation.
- [ ] Commission-disabled behavior is deterministic and tested.

### Booking lifecycle
- [ ] Every qualifying booking gets one ordinal only.
- [ ] One customer with many bookings consumes one allowance slot per qualifying booking.
- [ ] Reschedule does not consume a second slot.
- [ ] Duplicate requests/status events do not double count.
- [ ] Cancellation, no-show, month-crossing reschedule and unpaid bookings follow documented policy.
- [ ] Concurrent bookings at the threshold cannot receive duplicate ordinals.
- [ ] Cross-tenant resources cannot be counted/read/modified by another tenant.

### Money lifecycle
- [ ] Pending/failed/expired online attempts are not shown as collected.
- [ ] Paid payment reconciliation is idempotent.
- [ ] Multiple payment attempts/partials do not duplicate commission.
- [ ] Partial/full refund adjusts commission and net amounts correctly.
- [ ] Refund after payout produces a controlled recovery/negative-balance state.
- [ ] Cash counts consistently but is not represented as gateway-collected money.
- [ ] Cash collection and BookResa commission remittance are auditable and authorized.
- [ ] Commission earned, commission collected, receivable, workspace payable and payout paid are separate.
- [ ] Subscription payment remains separate from booking payment.
- [ ] Amount, currency, merchant and reference mismatch is rejected.

### Admin and deployment
- [ ] Super Admin can manage allowance, percentage and enabled state.
- [ ] All finance totals are currency-specific.
- [ ] Payout cannot exceed available balance and cannot be marked paid without external reference/evidence.
- [ ] Permissions, tenant scopes and audit trail are tested.
- [ ] Pest full suite, PHPStan/Larastan, syntax scan, changed-file Pint and Vite build pass.
- [ ] Actual Kashier sandbox checkout, server verification, signed webhook, duplicate delivery and refund are verified.
- [ ] Backup/restore rehearsal, production smoke tests, logs, scheduler and queue readiness are confirmed.

## 10. Phased implementation and exit gates

### Phase 0 — Finalize policy and funds path
Define the qualifying booking event, cancellation/no-show/refund rules, fee base, rounding, timezone, settings effective time, and cash responsibility. Obtain written provider confirmation about independent businesses, marketplace eligibility, split settlement/manual transfers, KYB, refunds and disputes. No live payment activation in this phase.

### Phase 1 — Monthly counter and Super Admin policy
Implement separate allowance/rate/enabled settings, policy snapshot, database-safe ordinal allocation and tests for per-workspace/month behavior and concurrency. Exit when boundary tests pass without external payment calls.

### Phase 2 — Booking commission ledger and online reconciliation
Implement booking-centric commission accounting for verified payments, retries, partial payments, refunds, reversals and backfill/reconciliation. Exit when the finance ledger matches test payments and historical data handling is proved.

### Phase 3 — Cash, receivable and settlement
Build approved cash reconciliation, commission receivable/remittance, payout reservation and external transfer confirmation. Add automation only if provider-approved and supported. Exit when every balance reflects a real auditable event.

### Phase 4 — Super Admin/workspace UX and reports
Add monthly counter and clear currency-aware statements, policy controls, finance metrics, audit events and tenant-isolation tests. Exit when UI totals reconcile to the ledger.

### Phase 5 — Production release
Run complete CI, review migrations, back up database, rehearse migration and rollback, perform real sandbox E2E/refund verification, then run production smoke tests. Live customer charges remain disabled until provider/legal funds-flow approval and all release gates are explicitly satisfied.

## 11. Production safety rules

1. Fetch and inspect latest main before any implementation; the audit SHA is only the baseline.
2. Follow the project's main-branch workflow unless explicitly asked otherwise.
3. Inspect tracked and untracked production files before switching/building. Never run destructive cleanup or force overwrite.
4. Use Hostinger PHP 8.4 CLI explicitly; do not run Composer/Artisan with the incompatible default version.
5. Review migrations and confirm a restorable backup before any production migration.
6. Do not enable online payments or commission collection as a side effect of code deployment.
7. Never commit real payment secrets or expose them in logs, audit context, chat or screenshots. Use approved secret handling/Super Admin settings.
8. Report code tests separately from provider E2E, live charge and actual money-transfer verification.
9. Record deployed SHA, migration status, test output, smoke checks and relevant log evidence.
10. A rollback must preserve financial history. Prefer a backward-compatible release or a forward fix after financial rows exist; do not drop live ledger data to roll back.

## 12. Other documentation that must stay in sync

- DOC/00-PROJECT-SPECIFICATION.md — product business model and payment context.
- DOC/03-DATABASE-ERD.md — counter, booking, commission, cash, receivable and payout relationships.
- DOC/06-PAYMENTS.md — online/cash money flows, refunds and actual settlement behavior.
- DOC/07-SUBSCRIPTIONS-USAGE-BILLING.md — separate unique-customer usage from booking commissions.
- DOC/10-SECURITY.md — tenant isolation, authorization, audit and safe financial state changes.
- DOC/13-TESTING-QA.md — policy, concurrency, ledger and provider test matrix.
- DOC/15-IMPLEMENTATION-CHECKLIST.md — completion evidence.
- DOC/17-PRODUCTION-READINESS.md — backup, migration, sandbox, settlement and activation gate.
- DOC/20-SUPER-ADMIN-CONTROL.md — global policy and financial workflow controls.
- DOC/README.md — index this document.

Update the relevant domain documents in the same implementation change set. Do not leave old “free customer profiles before commission” wording beside the implemented monthly booking policy.

## 13. Acceptance criteria

The implementation is complete only when:

1. Super Admin defaults to 1,000 qualifying bookings per workspace/month and a 10% commission on eligible excess bookings.
2. The operator can change the policy without a source edit, according to the documented snapshot/effective-time rule.
3. Booking count is monthly, per business and independent of customer count, plan limits and payment method.
4. Ordinals remain unique under concurrency, retries and reschedules.
5. Online and cash records reconcile correctly; collected, earned, receivable, payable, reserved and paid amounts remain distinct.
6. Failed payments, refunds, cancellations and post-payout reversals are covered.
7. Tenant isolation applies to counters, ledger rows, screens, exports and actions.
8. The UI and finance reports match the underlying ledger and are currency-aware.
9. Existing finance history is retained and any backfill/reconciliation is documented.
10. Automated tests, static analysis, syntax checks, changed-file style checks and production build pass.
11. Real Kashier sandbox checkout, webhook duplication, status and refund verification are complete.
12. Provider and legal/commercial requirements for the actual funds flow are approved.
13. A production backup, migration plan, smoke test and recovery plan are evidenced.
14. Live customer charges are not enabled before required release gates are complete.

## 14. Status at audit baseline

Completed for this audit:
- [x] Reviewed repository tree, payment/commission code paths, settings, payout screens, relevant migrations, and tests.
- [x] Identified customer-ordinal logic versus monthly booking-count requirement.
- [x] Identified 5% existing default versus 10% requested default.
- [x] Confirmed the current payout screen is manual rather than an automatic transfer.
- [x] Confirmed cash bookings bypass online gateway checkout.
- [x] Separated subscription customer usage from the future commission allowance.
- [x] Recorded code areas, risk controls, tests, phases, and release gates.

Not implemented at this baseline:
- [ ] Monthly per-workspace booking counter.
- [ ] 1,000 booking / 10% defaults.
- [ ] Independent commission enable switch.
- [ ] Booking-centric ledger and safe historical-data plan.
- [ ] Cash collection, commission receivable and remittance flow.
- [ ] Admin/workspace monthly statements and reporting changes.
- [ ] New tests and required migrations.
- [ ] Real Kashier sandbox end-to-end verification.
- [ ] Provider/business/legal approval for marketplace funds flow.
- [ ] Production deployment of the new model.

**End of document.**