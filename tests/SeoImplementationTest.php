<?php
declare(strict_types=1);

/**
 * Enterprise SEO, Meta Engine, Robots, Sitemap & Structured Data Verification Suite
 * MS TRAVEL HUB — Global Visa Management Portal
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Services\SeoService;

class SeoImplementationTest
{
    private int $passCount = 0;
    private int $failCount = 0;

    public function runAll(): void
    {
        echo "====================================================================\n";
        echo "  MS TRAVEL HUB — COMPREHENSIVE SEO & STRUCTURED DATA TEST SUITE    \n";
        echo "====================================================================\n\n";

        $this->testSeoServiceMethods();
        $this->testRobotsTxtIntegrity();
        $this->testSitemapXmlIntegrity();
        $this->testManifestJsonIntegrity();
        $this->testHtaccessConfiguration();
        $this->testViewTemplatesSeoIntegration();

        echo "\n====================================================================\n";
        echo "  SEO TEST SUMMARY: {$this->passCount} PASSED, {$this->failCount} FAILED\n";
        echo "====================================================================\n";

        if ($this->failCount > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $name, string $details = ''): void
    {
        if ($condition) {
            echo "  [PASS] {$name}" . ($details ? " — {$details}" : "") . "\n";
            $this->passCount++;
        } else {
            echo "  [FAIL] {$name}" . ($details ? " — {$details}" : "") . "\n";
            $this->failCount++;
        }
    }

    private function testSeoServiceMethods(): void
    {
        echo "--- 1. SEOSERVICE CLASS & TAG GENERATION AUDIT ---\n";

        $base = SeoService::getBaseUrl();
        $this->assert($base === 'https://mshorizonuae.com', "SeoService::getBaseUrl returns canonical domain: {$base}");

        $canonical = SeoService::getCanonicalUrl('/track');
        $this->assert($canonical === 'https://mshorizonuae.com/track', "SeoService::getCanonicalUrl accurately formats path: {$canonical}");

        $isPriv = SeoService::isPrivatePath('/dashboard');
        $this->assert($isPriv === true, "SeoService::isPrivatePath detects internal authenticated path /dashboard");

        $isPub = SeoService::isPrivatePath('/track');
        $this->assert($isPub === false, "SeoService::isPrivatePath detects public path /track as non-private");

        $tags = SeoService::renderTags([
            'title' => 'Test Track Page',
            'description' => 'Test tracking description for visa verification.',
            'canonical' => 'https://mshorizonuae.com/track',
            'robots' => 'index, follow'
        ]);

        $this->assert(str_contains($tags, '<title>Test Track Page — MS TRAVEL HUB</title>'), "renderTags generates branded page title");
        $this->assert(str_contains($tags, '<meta name="description" content="Test tracking description for visa verification.">'), "renderTags generates meta description");
        $this->assert(str_contains($tags, '<meta name="robots" content="index, follow">'), "renderTags generates robots directive");
        $this->assert(str_contains($tags, '<link rel="canonical" href="https://mshorizonuae.com/track">'), "renderTags generates canonical link");
        $this->assert(str_contains($tags, '<meta property="og:site_name" content="MS Travel Hub">'), "renderTags generates Open Graph site_name");
        $this->assert(str_contains($tags, '<meta property="og:image" content="https://mshorizonuae.com/assets/images/logo.png">'), "renderTags generates Open Graph logo image");
        $this->assert(str_contains($tags, '<meta name="twitter:card" content="summary_large_image">'), "renderTags generates Twitter large image card");
        $this->assert(str_contains($tags, '<link rel="manifest" href="/manifest.json">'), "renderTags references Web App Manifest");

        $ldJson = SeoService::renderStructuredData();
        $this->assert(str_contains($ldJson, '"@type": "TravelAgency"'), "renderStructuredData includes Schema.org TravelAgency");
        $this->assert(str_contains($ldJson, '"@type": "WebSite"'), "renderStructuredData includes Schema.org WebSite");
        $this->assert(str_contains($ldJson, '"telephone": "+971585909349"'), "renderStructuredData includes official UAE contact telephone");
    }

    private function testRobotsTxtIntegrity(): void
    {
        echo "\n--- 2. ROBOTS.TXT INTEGRITY AUDIT ---\n";

        $robotsPath = dirname(__DIR__) . '/public/robots.txt';
        $this->assert(file_exists($robotsPath), "public/robots.txt file exists");

        $content = file_get_contents($robotsPath);
        $this->assert(str_contains($content, 'User-agent: *'), "robots.txt targets wildcard user agent");
        $this->assert(str_contains($content, 'Allow: /track'), "robots.txt explicitly allows public visa tracker /track");
        $this->assert(str_contains($content, 'Allow: /portal/track'), "robots.txt explicitly allows public visa tracker /portal/track");
        $this->assert(str_contains($content, 'Disallow: /admin'), "robots.txt disallows sensitive admin areas");
        $this->assert(str_contains($content, 'Disallow: /api/'), "robots.txt disallows raw API endpoints");
        $this->assert(str_contains($content, 'Sitemap: https://mshorizonuae.com/sitemap.xml'), "robots.txt references official production sitemap");
    }

    private function testSitemapXmlIntegrity(): void
    {
        echo "\n--- 3. SITEMAP.XML INTEGRITY AUDIT ---\n";

        $sitemapPath = dirname(__DIR__) . '/public/sitemap.xml';
        $this->assert(file_exists($sitemapPath), "public/sitemap.xml file exists");

        $xml = file_get_contents($sitemapPath);
        $this->assert(str_contains($xml, '<?xml version="1.0" encoding="UTF-8"?>'), "sitemap.xml has valid XML declaration");

        // Parse XML to guarantee strict well-formedness
        $doc = simplexml_load_string($xml);
        $this->assert($doc !== false, "sitemap.xml parses cleanly as valid XML");

        $urls = [];
        if ($doc !== false) {
            foreach ($doc->url as $u) {
                $urls[] = (string)$u->loc;
            }
        }

        $this->assert(in_array('https://mshorizonuae.com/', $urls, true), "sitemap.xml contains homepage URL");
        $this->assert(in_array('https://mshorizonuae.com/track', $urls, true), "sitemap.xml contains /track URL");
        $this->assert(in_array('https://mshorizonuae.com/portal/track', $urls, true), "sitemap.xml contains /portal/track URL");
        $this->assert(in_array('https://mshorizonuae.com/portal/login', $urls, true), "sitemap.xml contains /portal/login URL");
        $this->assert(!str_contains($xml, 'visatrack.mstravelhub.com'), "sitemap.xml strictly purged of obsolete staging domains");
    }

    private function testManifestJsonIntegrity(): void
    {
        echo "\n--- 4. MANIFEST.JSON PWA AUDIT ---\n";

        $manifestPath = dirname(__DIR__) . '/public/manifest.json';
        $this->assert(file_exists($manifestPath), "public/manifest.json file exists");

        $jsonStr = file_get_contents($manifestPath);
        $manifest = json_decode($jsonStr, true);
        $this->assert(is_array($manifest), "manifest.json parses cleanly as valid JSON");
        $this->assert(!empty($manifest['name']), "manifest.json specifies app name: " . ($manifest['name'] ?? ''));
        $this->assert(($manifest['theme_color'] ?? '') === '#e11d48', "manifest.json matches brand theme_color #e11d48");
        $this->assert(($manifest['start_url'] ?? '') === '/track', "manifest.json sets public tracking as start_url");
        $this->assert(!empty($manifest['icons']), "manifest.json contains icon assets for mobile devices");
    }

    private function testHtaccessConfiguration(): void
    {
        echo "\n--- 5. APACHE .HTACCESS DIRECTIVES AUDIT ---\n";

        $htaccessPath = dirname(__DIR__) . '/public/.htaccess';
        $this->assert(file_exists($htaccessPath), "public/.htaccess exists");

        $content = file_get_contents($htaccessPath);
        $this->assert(str_contains($content, '<Files "manifest.json">'), ".htaccess explicitly whitelists manifest.json");
        $this->assert(str_contains($content, 'Header set X-Content-Type-Options "nosniff"'), ".htaccess enforces nosniff security header");
    }

    private function testViewTemplatesSeoIntegration(): void
    {
        echo "\n--- 6. VIEW TEMPLATES SEO INTEGRATION AUDIT ---\n";

        $headerLayout = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/header.php');
        $this->assert(str_contains($headerLayout, 'SeoService::renderTags'), "layouts/header.php renders SeoService::renderTags");
        $this->assert(str_contains($headerLayout, 'SeoService::renderStructuredData'), "layouts/header.php renders SeoService::renderStructuredData");

        $portalHeader = file_get_contents(dirname(__DIR__) . '/app/Views/portal/header.php');
        $this->assert(str_contains($portalHeader, 'SeoService::renderTags'), "portal/header.php renders SeoService::renderTags");

        $portalTrack = file_get_contents(dirname(__DIR__) . '/app/Views/portal/track.php');
        $this->assert(str_contains($portalTrack, 'Track Visa Application Status Online'), "portal/track.php contains targeted SEO title");
        $this->assert(str_contains($portalTrack, 'WebApplication'), "portal/track.php contains WebApplication Schema.org definition");

        $staffLogin = file_get_contents(dirname(__DIR__) . '/app/Views/auth/login.php');
        $this->assert(str_contains($staffLogin, 'SeoService::renderTags'), "auth/login.php integrates SeoService");

        $applicantLogin = file_get_contents(dirname(__DIR__) . '/app/Views/portal/login.php');
        $this->assert(str_contains($applicantLogin, 'SeoService::renderTags'), "portal/login.php integrates SeoService");

        $agentLogin = file_get_contents(dirname(__DIR__) . '/app/Views/agent-portal/login.php');
        $this->assert(str_contains($agentLogin, 'SeoService::renderTags'), "agent-portal/login.php integrates SeoService");
    }
}

(new SeoImplementationTest())->runAll();
