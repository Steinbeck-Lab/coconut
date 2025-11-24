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
        // Core security directives
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE);

        // Form action - allow both dev and prod domains (HTTPS only)
        $policy
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FORM_ACTION, 'https://dev.coconut.naturalproducts.net')
            ->add(Directive::FORM_ACTION, 'https://coconut.naturalproducts.net');

        // Basic asset sources
        $policy
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::STYLE, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF, 'data:')
            ->add(Directive::CONNECT, Keyword::SELF);

        // Third-party services
        $policy
            ->add(Directive::STYLE, 'https://fonts.googleapis.com', 'https://unpkg.com')
            ->add(Directive::SCRIPT, 'https://matomo.nfdi4chem.de', 'https://cdn.jsdelivr.net')
            ->add(Directive::CONNECT, 'https://matomo.nfdi4chem.de')
            ->add(Directive::IMG, 'https://matomo.nfdi4chem.de');

        // Add Coconut-specific external sources
        $this->addCoconutSources($policy);

        // Unified rules for all environments
        $this->addUnifiedRules($policy);
    }

    private function addUnifiedRules(Policy $policy): void
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
            ->add(Directive::STYLE, 'https://cdnjs.cloudflare.com')
            ->add(Directive::SCRIPT, 'https://code.jquery.com')
            ->add(Directive::SCRIPT, 'https://cdnjs.cloudflare.com');

        // Allow build assets from Coconut domains (production and dev)
        $policy
            ->add(Directive::FONT, 'https://coconut.naturalproducts.net', 'https://dev.coconut.naturalproducts.net')
            ->add(Directive::STYLE, 'https://coconut.naturalproducts.net', 'https://dev.coconut.naturalproducts.net')
            ->add(Directive::SCRIPT, 'https://coconut.naturalproducts.net', 'https://dev.coconut.naturalproducts.net')
            ->add(Directive::IMG, 'https://coconut.naturalproducts.net', 'https://dev.coconut.naturalproducts.net');

        // Add nonce for inline scripts. This is automatically handled by spatie/laravel-csp when nonce_enabled is true
        $policy->addNonce(Directive::SCRIPT);

        // Keep unsafe-inline for styles temporarily (needed for Alpine.js inline styles and dynamic style attributes)
        $policy->add(Directive::STYLE, Keyword::UNSAFE_INLINE);
        $policy->add(Directive::SCRIPT, Keyword::UNSAFE_EVAL);

        // Security enhancements - automatically upgrade HTTP to HTTPS
        $policy->add(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE);
    }

    /**
     * Add Coconut-specific external sources.
     * For runtime-configurable sources, use config/csp.php with CSP_ADDITIONAL_* env variables.
     */
    private function addCoconutSources(Policy $policy): void
    {
        // Image sources
        $policy
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::IMG, 'data:')
            ->add(Directive::IMG, 'blob:')
            ->add(Directive::IMG, 'https://ui-avatars.com')
            ->add(Directive::IMG, '*.amazonaws.com')
            ->add(Directive::IMG, '*.s3.amazonaws.com')
            ->add(Directive::IMG, 'https://s3.uni-jena.de')
            ->add(Directive::IMG, 'https://www.nfdi4chem.de')
            ->add(Directive::IMG, 'https://upload.wikimedia.org')
            ->add(Directive::IMG, 'https://api.cheminf.studio')
            ->add(Directive::IMG, 'https://coconut.naturalproducts.net')
            ->add(Directive::IMG, 'https://github.com')
            ->add(Directive::IMG, 'https://raw.githubusercontent.com')
            ->add(Directive::IMG, 'https://www.gstatic.com')
            ->add(Directive::IMG, 'https://developers.google.com');

        // Local development image sources (depict service)
        if (! app()->environment('production')) {
            $policy->add(Directive::IMG, ['http://localhost:*', 'https://localhost:*', 'http://127.0.0.1:*', 'https://127.0.0.1:*']);
        }

        // Connection sources - External APIs
        $policy
            ->add(Directive::CONNECT, env('AWS_ENDPOINT', 'https://s3.uni-jena.de'))
            ->add(Directive::CONNECT, env('EUROPEPMC_WS_API', 'https://www.ebi.ac.uk/europepmc/webservices/rest/search'))
            ->add(Directive::CONNECT, env('CROSSREF_WS_API', 'https://api.crossref.org/works/'))
            ->add(Directive::CONNECT, env('DATACITE_WS_API', 'https://api.datacite.org/dois/'))
            ->add(Directive::CONNECT, env('NFDI_REDIRECT_URL', 'https://coconut.naturalproducts.net'))
            ->add(Directive::CONNECT, env('CM_PUBLIC_API', 'https://api.cheminf.studio'))
            ->add(Directive::CONNECT, 'https://coconut.naturalproducts.net')
            ->add(Directive::CONNECT, 'https://dev.coconut.naturalproducts.net')
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
            ->add(Directive::SCRIPT, '*.matomo.nfdi4chem.de/')
            ->add(Directive::SCRIPT, 'https://dev.coconut.naturalproducts.net');

        // Frame sources
        $policy
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::FRAME, '*.tawk.to')
            ->add(Directive::FRAME, 'https://embed.tawk.to')
            ->add(Directive::FRAME, 'https://coconut.naturalproducts.net')
            ->add(Directive::FRAME, 'https://dev.coconut.naturalproducts.net')
            ->add(Directive::FRAME, 'data:');
    }
}
