<?php

namespace App\Http\Controllers;

use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class SeoController
{
    public function sitemap(): Response
    {
        $xml = Cache::remember(
            'bookresa:sitemap:xml',
            now()->addMinutes(10),
            function (): string {
                $urls = collect([
                    [
                        'loc' => route('home'),
                    ],
                ]);

                Tenant::query()
                    ->where('status', TenantStatus::Active)
                    ->orderBy('id')
                    ->get(['slug', 'updated_at'])
                    ->each(function (Tenant $tenant) use ($urls): void {
                        $urls->push([
                            'loc' => route('public.booking.show', ['tenant' => $tenant->slug]),
                            'lastmod' => $tenant->updated_at?->toAtomString(),
                        ]);
                    });

                return view('seo.sitemap', ['urls' => $urls])->render();
            },
        );

        return response($xml)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $content = implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard/',
            'Disallow: /admin/',
            'Disallow: /onboarding/',
            'Disallow: /payments/',
            'Disallow: /webhooks/',
            'Sitemap: '.route('sitemap'),
            '',
        ]);

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
