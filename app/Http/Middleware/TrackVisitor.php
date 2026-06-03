<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    private const SOCIAL_DOMAINS = [
        'facebook.com', 'fb.com', 'instagram.com', 'twitter.com', 'x.com',
        'youtube.com', 'linkedin.com', 'pinterest.com', 'tiktok.com', 't.co',
        'l.facebook.com', 'lm.facebook.com',
    ];

    private const SEARCH_DOMAINS = [
        'google.com', 'google.co.in', 'bing.com', 'yahoo.com', 'duckduckgo.com',
        'baidu.com', 'yandex.com', 'ecosia.org',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only track GET requests to web pages (skip API, assets, admin)
        if (
            !$request->isMethod('GET')
            || $request->expectsJson()
            || $request->is('api/*', 'admin/*', 'webhook/*', 'livewire/*', '_debugbar/*')
            || str_contains($request->path(), '.')
        ) {
            return $next($request);
        }

        try {
            $sessionId = $request->session()->getId();

            // One row per session — skip if already tracked
            if ($request->session()->has('_visitor_tracked')) {
                return $next($request);
            }

            $utmSource = $request->query('utm_source');
            $utmMedium = $request->query('utm_medium');
            $utmCampaign = $request->query('utm_campaign');
            $referrer = $request->headers->get('referer', '');
            $referrerDomain = $this->extractDomain($referrer);
            $appDomain = $this->extractDomain(config('app.url'));

            // Skip self-referrals
            if ($referrerDomain && $referrerDomain === $appDomain) {
                $referrer = '';
                $referrerDomain = '';
            }

            $sourceType = $this->classifySource($utmSource, $utmMedium, $referrerDomain, $request);

            SiteVisit::create([
                'session_id' => $sessionId,
                'source_type' => $sourceType,
                'utm_source' => $utmSource ? substr($utmSource, 0, 100) : null,
                'utm_medium' => $utmMedium ? substr($utmMedium, 0, 100) : null,
                'utm_campaign' => $utmCampaign ? substr($utmCampaign, 0, 150) : null,
                'utm_content' => $request->query('utm_content') ? substr($request->query('utm_content'), 0, 150) : null,
                'utm_term' => $request->query('utm_term') ? substr($request->query('utm_term'), 0, 150) : null,
                'referrer' => $referrer ? substr($referrer, 0, 500) : null,
                'referrer_domain' => $referrerDomain ?: null,
                'landing_page' => substr('/' . ltrim($request->path(), '/'), 0, 500),
                'device' => $this->detectDevice($request->userAgent()),
                'ip_address' => $request->ip(),
                'user_id' => auth()->id(),
            ]);

            $request->session()->put('_visitor_tracked', true);
        } catch (\Throwable $e) {
            Log::warning('TrackVisitor failed', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }

    private function classifySource(?string $utmSource, ?string $utmMedium, ?string $referrerDomain, Request $request): string
    {
        // Has UTM params → check if it's an ad
        if ($utmSource) {
            $medium = strtolower($utmMedium ?? '');
            if (in_array($medium, ['cpc', 'ppc', 'paid', 'cpm', 'display', 'retargeting', 'paid_social'])) {
                return 'ad';
            }
            // Has gclid or fbclid → ad click
            if ($request->query('gclid') || $request->query('fbclid') || $request->query('wbraid') || $request->query('gbraid')) {
                return 'ad';
            }
            // UTM source is a social platform
            if ($this->isSocialDomain($utmSource)) {
                return 'social';
            }
            // Has UTM but not ad — treat as campaign/referral
            return 'referral';
        }

        // No UTM — check gclid/fbclid (auto-tagged ads)
        if ($request->query('gclid') || $request->query('fbclid') || $request->query('wbraid') || $request->query('gbraid')) {
            return 'ad';
        }

        // Classify by referrer domain
        if ($referrerDomain) {
            if ($this->isSearchDomain($referrerDomain)) {
                return 'organic';
            }
            if ($this->isSocialDomain($referrerDomain)) {
                return 'social';
            }
            return 'referral';
        }

        return 'direct';
    }

    private function isSocialDomain(string $domain): bool
    {
        $domain = strtolower($domain);
        foreach (self::SOCIAL_DOMAINS as $social) {
            if ($domain === $social || str_ends_with($domain, '.' . $social)) {
                return true;
            }
        }
        return false;
    }

    private function isSearchDomain(string $domain): bool
    {
        $domain = strtolower($domain);
        foreach (self::SEARCH_DOMAINS as $search) {
            if ($domain === $search || str_ends_with($domain, '.' . $search)) {
                return true;
            }
        }
        return false;
    }

    private function extractDomain(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? strtolower(preg_replace('/^www\./', '', $host)) : '';
    }

    private function detectDevice(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'desktop';
        }
        $ua = strtolower($userAgent);
        if (preg_match('/mobile|android.*mobile|iphone|ipod|opera mini|iemobile/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            return 'tablet';
        }
        return 'desktop';
    }
}
