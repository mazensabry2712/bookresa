# BookResa — Localization, Themes and SEO

## Languages
Initial:
- Arabic (ar)
- English (en)

## Locale
Use locale-aware routes where appropriate, such as /ar/... and /en/.... Middleware validates locale and sets Laravel locale.

## UI translations
Use Laravel localization for navigation, actions, validation, status labels and system messages.

## Business content
Business/service public content can store localized fields, for example JSON keyed by locale.

## RTL/LTR
Arabic = rtl.
English = ltr.
Layouts/components must be direction-safe from the first implementation.

## Themes
First-class:
- Light
- Dark
Optional later:
- System

Use Tailwind dark mode and centralized design tokens/CSS variables. Apply client theme early to reduce flash.

## SEO
Indexable public pages may include:
- marketing pages
- public business pages
- public service pages
- suitable public booking landings

Private dashboard/admin routes must not be indexed.

## SEO metadata
Title, description, canonical, hreflang, Open Graph, social image and JSON-LD where relevant.

## Multilingual SEO
Arabic/English equivalents should reference each other with hreflang. Canonicals must be deterministic.

## Sitemap
Use spatie/laravel-sitemap.

## Robots
public/robots.txt allows public pages, disallows private areas, and references sitemap.

## Performance
Public pages remain Blade SSR with minimal JS, optimized images, stable-data caching and no API waterfalls.
