<?php

namespace App\Http\Middleware;

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

            $locale = is_string($sessionLocale) && in_array($sessionLocale, $supportedLocales, true)
                ? $sessionLocale
                : $request->getPreferredLanguage($supportedLocales);
        }

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
