<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Env;

/**
 * Enterprise SEO & Structured Metadata Engine
 * MS TRAVEL HUB — Global Visa Management Portal
 */
class SeoService
{
    private static array $customMeta = [];

    /**
     * Get official production base URL (strictly HTTPS)
     */
    public static function getBaseUrl(): string
    {
        $domain = Env::get('APP_URL') ?: Env::get('COMPANY_WEBSITE', 'https://mshorizonuae.com');
        return rtrim((string)$domain, '/');
    }

    /**
     * Set dynamic page metadata for the current request
     */
    public static function setPageMeta(array $meta): void
    {
        self::$customMeta = array_merge(self::$customMeta, $meta);
    }

    /**
     * Build absolute canonical URL from current request or custom path
     */
    public static function getCanonicalUrl(?string $path = null): string
    {
        $base = self::getBaseUrl();
        if ($path !== null) {
            return $base . '/' . ltrim($path, '/');
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $cleanUri = rtrim($uri, '/');
        if (empty($cleanUri)) {
            $cleanUri = '';
        }

        return $base . ($cleanUri ? $cleanUri : '/');
    }

    /**
     * Determine if current route is private/internal and should be noindexed
     */
    public static function isPrivatePath(?string $path = null): bool
    {
        $uri = $path ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
        $privatePrefixes = [
            '/dashboard',
            '/admin',
            '/superadmin',
            '/api',
            '/staff',
            '/users',
            '/applications',
            '/customers',
            '/documents',
            '/settings',
            '/audit',
            '/reports',
            '/payroll',
            '/inventory',
            '/portal/dashboard',
            '/portal/documents',
            '/portal/invoices',
            '/portal/wallet',
            '/portal/support',
            '/portal/appointments',
            '/portal/notifications',
            '/agent/dashboard',
            '/agent-portal/dashboard',
            '/supplier-portal/dashboard'
        ];

        foreach ($privatePrefixes as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render complete HTML meta tag block
     */
    public static function renderTags(?array $override = null): string
    {
        $meta = array_merge(self::$customMeta, $override ?? []);

        $companyName = Env::get('COMPANY_NAME', 'MS Travel Hub Global Visa Services');
        $rawTitle = $meta['title'] ?? 'MS TRAVEL HUB — Global Visa Management & Tracking Portal';
        $title = str_contains($rawTitle, 'MS TRAVEL HUB') ? $rawTitle : "{$rawTitle} — MS TRAVEL HUB";

        $description = $meta['description'] ?? 'Official MS Travel Hub Global Visa Management & Verification Portal. Fast-track visa processing, real-time application tracking, document attestation, and consular services across the UAE and worldwide.';
        $keywords = $meta['keywords'] ?? 'visa tracking, UAE visa status, track visa application, Dubai visa services, Schengen visa consultant, UK visa services, global visa portal, MS Travel Hub, MS Horizon, express visa processing';
        $canonical = $meta['canonical'] ?? self::getCanonicalUrl($meta['path'] ?? null);
        $robots = $meta['robots'] ?? (self::isPrivatePath() ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
        $ogType = $meta['og_type'] ?? 'website';
        $ogImage = $meta['og_image'] ?? (self::getBaseUrl() . '/assets/images/logo.png');

        $output = [];
        $output[] = '  <!-- Core SEO & Search Engine Directives -->';
        $output[] = '  <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        $output[] = '  <meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta name="keywords" content="' . htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta name="robots" content="' . htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta name="author" content="' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">';

        $output[] = '  <!-- Open Graph / Facebook / WhatsApp Preview Protocol -->';
        $output[] = '  <meta property="og:site_name" content="MS Travel Hub">';
        $output[] = '  <meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta property="og:type" content="' . htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta property="og:url" content="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta property="og:locale" content="en_US">';

        $output[] = '  <!-- Twitter / X Cards Protocol -->';
        $output[] = '  <meta name="twitter:card" content="summary_large_image">';
        $output[] = '  <meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">';
        $output[] = '  <meta name="twitter:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">';

        $output[] = '  <!-- Progressive Web App & Mobile Browser Integration -->';
        $output[] = '  <link rel="manifest" href="/manifest.json">';
        $output[] = '  <meta name="mobile-web-app-capable" content="yes">';
        $output[] = '  <meta name="apple-mobile-web-app-capable" content="yes">';
        $output[] = '  <meta name="apple-mobile-web-app-status-bar-style" content="default">';
        $output[] = '  <meta name="apple-mobile-web-app-title" content="MS Travel Hub">';

        return implode("\n", $output);
    }

    /**
     * Render Schema.org JSON-LD Structured Data
     */
    public static function renderStructuredData(?array $customSchema = null): string
    {
        $baseUrl = self::getBaseUrl();
        $companyName = Env::get('COMPANY_NAME', 'MS Travel Hub Global Visa Services');
        $phone = Env::get('COMPANY_PHONE', '0585909349');
        $email = Env::get('COMPANY_EMAIL', 'mstravelu@gmail.com');

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'TravelAgency',
                    '@id' => $baseUrl . '/#organization',
                    'name' => $companyName,
                    'alternateName' => 'MS Horizon Global Visa Services',
                    'url' => $baseUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $baseUrl . '/assets/images/logo.png',
                        'width' => 512,
                        'height' => 512
                    ],
                    'image' => $baseUrl . '/assets/images/logo.png',
                    'telephone' => '+971' . ltrim(preg_replace('/[^0-9]/', '', (string)$phone), '0'),
                    'email' => $email,
                    'priceRange' => '$$',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Dubai',
                        'addressCountry' => 'AE'
                    ],
                    'areaServed' => [
                        'United Arab Emirates',
                        'Saudi Arabia',
                        'Qatar',
                        'Kuwait',
                        'Oman',
                        'Bahrain',
                        'United Kingdom',
                        'European Union',
                        'Global'
                    ],
                    'serviceType' => [
                        'Visa Consultation & Processing',
                        'Tourist & Visit Visa Assistance',
                        'Employment & Golden Visa Services',
                        'Consular & Embassy Document Attestation',
                        'Real-Time Visa Application Tracking'
                    ],
                    'sameAs' => [
                        'https://mshorizonuae.com'
                    ]
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $baseUrl . '/#website',
                    'url' => $baseUrl,
                    'name' => 'MS Travel Hub Global Visa Services',
                    'publisher' => [
                        '@id' => $baseUrl . '/#organization'
                    ],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => $baseUrl . '/track?ref={search_term_string}',
                        'query-input' => 'required name=search_term_string'
                    ]
                ]
            ]
        ];

        if ($customSchema !== null) {
            $schema['@graph'][] = $customSchema;
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return "  <script type=\"application/ld+json\">\n" . $json . "\n  </script>";
    }
}
