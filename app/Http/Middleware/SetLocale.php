<?php

namespace App\Http\Middleware;

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Tenant\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_values(config('bookresa.locales', ['en']));
        $defaultLocale = config('bookresa.default_locale', 'en');

        $requestedLocale = $request->query('locale');

        if (is_string($requestedLocale) && in_array($requestedLocale, $supportedLocales, true)) {
            $locale = $requestedLocale;
            $request->session()->put('locale', $locale);
        } else {
            $sessionLocale = $request->session()->get('locale');

            if (is_string($sessionLocale) && in_array($sessionLocale, $supportedLocales, true)) {
                $locale = $sessionLocale;
            } else {
                $routeTenant = $request->route('tenant');
                $workspaceLocale = $routeTenant instanceof Tenant
                    ? BusinessProfile::query()
                        ->withoutGlobalScopes()
                        ->where('tenant_id', $routeTenant->getKey())
                        ->value('locale')
                    : null;

                $locale = is_string($workspaceLocale) && in_array($workspaceLocale, $supportedLocales, true)
                    ? $workspaceLocale
                    : $request->getPreferredLanguage($supportedLocales);
            }
        }

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
