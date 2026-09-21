# BookResa — Technical Decisions

## ADR-001 Modular Monolith
Chosen for fast delivery, simple deployment and strong internal boundaries.

## ADR-002 Shared database tenancy
One MySQL database with tenant_id on tenant-owned records for MVP.

## ADR-003 Blade-first
Chosen for low client-side overhead, SSR/SEO and a strong fit for dashboards and booking pages.

## ADR-004 Provider-neutral Payment Core
Kashier is the first provider. Vendor-specific code stays under Infrastructure/Payments.

## ADR-005 No Cashier for MVP
BookResa has custom unique-customer usage billing and two payment contexts. Provider-specific subscription coupling is avoided.

## ADR-006 No tenancy package for MVP
Current model is shared DB + tenant_id. Reconsider only if the data-isolation model changes.

## ADR-007 No SPA for MVP
No React/Vue/Inertia/Livewire stack. Use Blade + Alpine.

## ADR-008 Laravel localization
No translation package is needed for ar/en UI.

## ADR-009 Focused SEO
Application owns metadata; sitemap may use a focused package.

## ADR-010 Core billing owned by BookResa
Customer counting, pricing, usage and subscription rules live in the application domain.

## ADR-011 Redis-ready
Redis is a production acceleration layer, not an unnecessary local prerequisite.

## ADR-012 Minor-unit money
Financial amounts are stored as integer minor units.

## ADR-013 Pricing snapshots
Historical billing preserves its original calculation inputs.

## ADR-014 Business type is configuration
Business types provide defaults/recommendations and never create separate application implementations.

## Explicit MVP non-goals
Microservices, distributed event bus, Kubernetes, GraphQL, multiple frontend frameworks, per-business code forks and provider-specific domain coupling.
