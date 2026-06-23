<?php

namespace App\Support\Csp\Policies;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Value;

class CoconutPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $appOrigins = $this->configuredAppOrigins();

        // Core security directives
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE);

        // Form action - allow configured app hosts (HTTPS only)
        $policy->add(Directive::FORM_ACTION, Keyword::SELF);
        $this->addSources($policy, Directive::FORM_ACTION, $appOrigins);

        // Basic asset sources
        $policy
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::STYLE, Keyword::SELF)
            ->add(Directive::FONT, [Keyword::SELF, 'data:', 'https://fonts.scalar.com'])
            ->add(Directive::CONNECT, Keyword::SELF);

        // Third-party services
        $policy
            ->add(Directive::STYLE, ['https://fonts.googleapis.com', 'https://unpkg.com', 'https://cdn.jsdelivr.net', 'https://fonts.bunny.net'])
            ->add(Directive::FONT, ['https://fonts.bunny.net'])
            ->add(Directive::SCRIPT, ['https://matomo.nfdi4chem.de', 'https://cdn.jsdelivr.net', 'https://unpkg.com'])
            ->add(Directive::CONNECT, ['https://matomo.nfdi4chem.de', 'https://unpkg.com', 'https://fonts.scalar.com'])
            ->add(Directive::IMG, ['https://matomo.nfdi4chem.de', 'https://unpkg.com']);

        // Add Coconut-specific external sources
        $this->addCoconutSources($policy, $appOrigins);

        // Unified rules for all environments
        $this->addUnifiedRules($policy, $appOrigins);
    }

    /**
     * @return list<string>
     */
    private function configuredAppOrigins(): array
    {
        return $this->originsFromUrls([
            config('app.url'),
            config('services.coconut.public_url'),
        ]);
    }

    /**
     * @return list<string>
     */
    private function configuredStorageOrigins(): array
    {
        return $this->originsFromUrls([
            config('filesystems.disks.s3.url'),
            config('filesystems.disks.s3.downloads_url'),
            config('filesystems.disks.s3.endpoint'),
        ]);
    }

    /**
     * @param  list<string|null>  $urls
     * @return list<string>
     */
    private function originsFromUrls(array $urls): array
    {
        $origins = [];

        foreach ($urls as $url) {
            if ($origin = $this->originFromUrl($url)) {
                $origins[] = $origin;
            }
        }

        return array_values(array_unique($origins));
    }

    private function originFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
    }

    /**
     * @param  list<string|null>  $sources
     */
    private function addSources(Policy $policy, Directive $directive, array $sources): void
    {
        $filtered = array_values(array_filter($sources, fn ($source) => is_string($source) && $source !== ''));

        if ($filtered === []) {
            return;
        }

        $policy->add($directive, count($filtered) === 1 ? $filtered[0] : $filtered);
    }

    /**
     * @param  list<string>  $appOrigins
     */
    private function addUnifiedRules(Policy $policy, array $appOrigins): void
    {
        // Development server support (for local development with Vite)
        // Only add localhost sources in non-production environments
        if (! app()->environment('production')) {
            $policy
                ->add(Directive::SCRIPT, ['http://localhost:*', 'https://localhost:*', 'http://127.0.0.1:*', 'https://127.0.0.1:*'])
                ->add(Directive::STYLE, ['http://localhost:*', 'https://localhost:*', 'http://127.0.0.1:*', 'https://127.0.0.1:*'])
                ->add(Directive::FONT, ['http://localhost:*', 'https://localhost:*', 'http://127.0.0.1:*', 'https://127.0.0.1:*'])
                ->add(Directive::CONNECT, ['ws://localhost:*', 'wss://localhost:*', 'http://localhost:*', 'https://localhost:*', 'ws://127.0.0.1:*', 'wss://127.0.0.1:*', 'http://127.0.0.1:*', 'https://127.0.0.1:*']);

            // Frame ancestors - allow localhost for development
            $policy->add(Directive::FRAME_ANCESTORS, [Keyword::SELF, 'localhost:*', '127.0.0.1:*']);
        } else {
            // Production only - strict frame ancestors
            $policy->add(Directive::FRAME_ANCESTORS, Keyword::SELF);
        }

        // CDN sources for external libraries
        $policy
            ->add(Directive::STYLE, ['https://cdnjs.cloudflare.com'])
            ->add(Directive::SCRIPT, ['https://code.jquery.com'])
            ->add(Directive::SCRIPT, ['https://cdnjs.cloudflare.com']);

        // Allow build assets from configured Coconut app hosts
        $this->addSources($policy, Directive::FONT, $appOrigins);
        $this->addSources($policy, Directive::STYLE, $appOrigins);
        $this->addSources($policy, Directive::SCRIPT, $appOrigins);
        $this->addSources($policy, Directive::IMG, $appOrigins);

        $policy = $this->addNonce($policy);

        // Keep unsafe-inline for styles temporarily (needed for Alpine.js inline styles and dynamic style attributes)
        $policy->add(Directive::STYLE, Keyword::UNSAFE_INLINE);
        $policy->add(Directive::SCRIPT, Keyword::UNSAFE_EVAL);
        // Security enhancements - automatically upgrade HTTP to HTTPS
        $policy->add(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE);
    }

    private function addNonce(Policy $policy): Policy
    {
        // Add nonce for inline scripts, but NOT for Horizon routes.
        // When both nonce and unsafe-inline are present, browsers ignore unsafe-inline.
        // Horizon uses inline scripts without nonces, so we skip nonce and add unsafe-inline instead.
        $isHorizonRoute = request()->is(config('horizon.path', 'horizon').'*');

        if ($isHorizonRoute) {
            $policy->add(Directive::SCRIPT, Keyword::UNSAFE_INLINE);
        } else {
            $policy->addNonce(Directive::SCRIPT);
        }

        return $policy;
    }

    /**
     * Add Coconut-specific external sources.
     *
     * @param  list<string>  $appOrigins
     */
    private function addCoconutSources(Policy $policy, array $appOrigins): void
    {
        $storageOrigins = $this->configuredStorageOrigins();
        $avatarOrigin = $this->originFromUrl(config('services.avatars.url'));
        $cheminfOrigin = $this->originFromUrl(rtrim((string) config('services.cheminf.api_url'), '/'));

        // Image sources
        $policy
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::IMG, 'data:')
            ->add(Directive::IMG, 'blob:')
            ->add(Directive::IMG, array_filter([$avatarOrigin, ...$storageOrigins, ...$appOrigins]))
            ->add(Directive::IMG, 'https://www.nfdi4chem.de')
            ->add(Directive::IMG, 'https://upload.wikimedia.org')
            ->add(Directive::IMG, 'https://*.naturalproducts.net')
            ->add(Directive::IMG, 'https://github.com')
            ->add(Directive::IMG, 'https://raw.githubusercontent.com')
            ->add(Directive::IMG, 'https://www.gstatic.com')
            ->add(Directive::IMG, 'https://developers.google.com');

        // Local development image sources (depict service)
        if (! app()->environment('production')) {
            $policy->add(Directive::IMG, ['http://localhost:*', 'https://localhost:*', 'http://127.0.0.1:*', 'https://127.0.0.1:*']);
        }

        // Connection sources - External APIs
        $this->addSources($policy, Directive::CONNECT, [
            ...$storageOrigins,
            ...$appOrigins,
            config('services.citation.europepmc_url'),
            config('services.citation.crossref_url'),
            config('services.citation.datacite_url'),
            config('services.regapp.redirect'),
            $this->originFromUrl(config('services.regapp.oidc_base_url')),
            config('services.cheminf.api_url'),
            $cheminfOrigin,
        ]);

        $policy
            ->add(Directive::CONNECT, '*.tawk.to')
            ->add(Directive::CONNECT, 'wss://*.tawk.to')
            ->add(Directive::CONNECT, 'https://cdn.jsdelivr.net')
            ->add(Directive::CONNECT, 'https://cdnjs.cloudflare.com')
            ->add(Directive::CONNECT, 'http://matomo.nfdi4chem.de');

        // Font sources
        $policy
            ->add(Directive::FONT, 'https://fonts.googleapis.com')
            ->add(Directive::FONT, 'https://fonts.gstatic.com');

        // Script sources - External JavaScript libraries
        $policy
            ->add(Directive::SCRIPT, '*.tawk.to')
            ->add(Directive::SCRIPT, 'https://embed.tawk.to')
            ->add(Directive::SCRIPT, 'https://matomo.nfdi4chem.de/');

        $this->addSources($policy, Directive::SCRIPT, $appOrigins);

        // Frame sources
        $policy
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::FRAME, '*.tawk.to')
            ->add(Directive::FRAME, 'https://embed.tawk.to')
            ->add(Directive::FRAME, 'data:');

        $this->addSources($policy, Directive::FRAME, $appOrigins);
    }
}
