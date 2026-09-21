# BookResa — Technical Decisions

## ADR-001 Modular Monolith
Chosen for fast delivery, simple deployment and strong internal boundaries.

## ADR-002 Shared-database multi-tenancy
One MySQL database with tenant_id on tenant-owned records for MVP.

## ADR-003 Blade-first
Chosen for low client-side overhead, SSR/SEO and dashboard/booking fit.

## ADR-004 Provider-neutral Payment Core
Kashier is the first gateway; provider details live behind an application-owned contract.

## ADR-005 No Cashier for MVP
BookResa has custom unique-customer usage billing and two payment contexts, so provider-specific subscription coupling is avoided.

## ADR-006 No tenancy package for MVP
Current design is shared DB + tenant_id. Reconsider only if the isolation model changes.

## ADR-007 No SPA for MVP
No React/Vue/Inertia/Livewire stack. Use Blade + Alpine.

## ADR-008 Laravel localization
No localization package for ar/en UI.

## ADR-009 Focused SEO
Application owns metadata; sitemap can use a focused package.

## ADR-010 Core billing owned by BookResa
Customer counting, pricing, usage and subscription rules live in the domain.

## ADR-011 Redis-ready
Redis is a production acceleration layer, not an unnecessary local prerequisite.

## ADR-012 Minor-unit money
Do not store financial values as floating-point numbers.

## ADR-013 Billing snapshots
Historical billing preserves its calculation inputs.

## ADR-014 Business type is configuration
Business type supplies defaults/recommendations, never separate codebases.

## Explicit MVP non-goals
Microservices, distributed event bus, Kubernetes, GraphQL, multiple frontend frameworks, per-business code forks and provider-specific business logic.
