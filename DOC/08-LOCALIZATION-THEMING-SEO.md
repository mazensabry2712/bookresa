# BookResa — Localization, Themes and SEO

## Languages
Initial:
- Arabic (ar)
- English (en)

Architecture must allow future languages.

## Locale
Use locale-aware routes where appropriate, e.g. /ar/... and /en/.... Validate locale in middleware and set the Laravel application locale.

## UI translations
Use Laravel localization resources for navigation, actions, validation, statuses, notifications and system messages.

## Business content
Business/service public content can store localized values (for example JSON keyed by locale).

## RTL/LTR
Arabic uses rtl. English uses ltr. Layouts and components must be direction-safe from the start.

## Themes
First-class:
- Light
- Dark
Optional later:
- System

Use Tailwind dark mode and centralized design tokens/CSS variables. Apply stored browser preference early to reduce theme flash.

## SEO scope
Primarily public pages:
- marketing pages
- public business pages
- public service pages
- indexable public booking landings where appropriate

Dashboard/admin/private routes should not be indexable.

## SEO metadata
Support:
- title
- meta description
- canonical
- hreflang
- Open Graph
- social image
- JSON-LD where relevant

## Multilingual SEO
Arabic/English equivalents should link with hreflang when both exist. Canonical URLs must be deterministic.

## Sitemap
Use spatie/laravel-sitemap.

## Robots
Serve robots.txt that permits public pages, blocks private areas and references the sitemap.

## Performance
Keep public pages Blade SSR, lightweight, image-optimized and free of unnecessary API waterfalls.
