@php
    $localeUrls = app(\App\Support\LocaleUrls::class);
    // Slug-based detail pages (guides/pages/news .show) pass per-locale alternates as a prop, since
    // their slugs differ per locale; everything else uses the generic path-swap.
    $seoAlternates = $page['props']['seoAlternates'] ?? $localeUrls->alternates(request());

    $locale = app()->getLocale();
    $seoDefaults = app(\App\Services\Cms\GlobalResolver::class)->seoDefaults();
    $seo = $page['props']['seo'] ?? [];
    $seoTitle = $seo['title'] ?? $seoDefaults['title'];
    $seoDescription = $seo['description'] ?? $seoDefaults['description'];
    $canonicalUrl = collect($seoAlternates)->firstWhere('code', $locale)['url'] ?? request()->url();
    $ogImage = $seo['image'] ?? $seoDefaults['image'];
    $isPreview = ($page['props']['isPreview'] ?? false) || ($seo['noindex'] ?? false);
@endphp
<!DOCTYPE html>
<html lang="{{ $localeUrls->hreflang($locale) }}" data-appearance="{{ $appearance ?? 'system' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="{{ $seoDescription }}">
        @if ($isPreview)
            <meta name="robots" content="noindex, nofollow">
        @endif

        {{-- Open Graph / Twitter card — server-rendered for crawlers since SSR is off (ТЗ §15) --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ trans('ui.site.short_name') }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:locale" content="{{ $localeUrls->hreflang($locale) }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        @if ($seoAlternates !== [])
            <link rel="canonical" href="{{ $canonicalUrl }}">
            @foreach ($seoAlternates as $alternate)
                <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['url'] }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ collect($seoAlternates)->firstWhere('code', $localeUrls->defaultCode())['url'] ?? url($localeUrls->defaultCode()) }}">
            <link rel="alternate" type="application/rss+xml" title="{{ trans('ui.news.heading') }}" href="{{ route('news.rss', ['locale' => $locale]) }}">
        @endif

        {{-- Matomo Analytics — config via meta tags so the bootstrap stays an external 'self' file (no inline script). --}}
        @if (! $isPreview && config('matomo.url') && config('matomo.site_id'))
            <meta name="matomo-url" content="{{ config('matomo.url') }}">
            <meta name="matomo-site-id" content="{{ config('matomo.site_id') }}">
            <script src="/js/matomo.js" defer></script>
        @endif

        {{-- Schema.org JSON-LD --}}
        @if (isset($page['props']['schema']))
            <script type="application/ld+json">
                {!! json_encode($page['props']['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
            </script>
        @endif

        {{-- schema.org SpecialAnnouncement per active alert — makes live emergency warnings
             machine-readable for search engines and voice assistants (ТЗ §2, §15.1). --}}
        @foreach ($page['props']['activeAlerts'] ?? [] as $activeAlert)
            <script type="application/ld+json">
                {!! json_encode(array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'SpecialAnnouncement',
                    'name' => $activeAlert['title'] ?? $activeAlert['level_label'] ?? '',
                    'text' => $activeAlert['body'] ?? $activeAlert['title'] ?? '',
                    'category' => 'https://www.wikidata.org/wiki/Q3241045',
                    'datePosted' => $activeAlert['published_at'] ?? null,
                    'expires' => $activeAlert['expires_at'] ?? null,
                    'url' => $activeAlert['url'] ?? null,
                    'spatialCoverage' => ['@type' => 'AdministrativeArea', 'name' => 'Tajikistan'],
                ], fn ($value) => $value !== null && $value !== ''), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
            </script>
        @endforeach

        {{-- No-flash theme bootstrap — external 'self' file (reads data-appearance on <html>). --}}
        <script src="/js/theme.js"></script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.ico" type="image/x-icon">
        <link rel="apple-touch-icon" href="/favicon.ico">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $seoTitle }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
