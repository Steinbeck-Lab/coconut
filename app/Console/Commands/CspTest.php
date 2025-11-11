<?php

namespace App\Console\Commands;

use App\Support\Csp\Policies\CoconutPolicy;
use Illuminate\Console\Command;
use Spatie\Csp\Policy;

class CspTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csp:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and display CSP policies for the current environment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("CSP Policy Test - Environment: " . app()->environment());
        $this->info("CSP Enabled: " . (config('csp.enabled') ? 'Yes' : 'No'));
        $this->info("Nonce Enabled: " . (config('csp.nonce_enabled') ? 'Yes' : 'No'));
        $this->line('');

        // Create a policy instance and configure it
        $coconutPolicy = new CoconutPolicy();
        $policy = new Policy();
        $coconutPolicy->configure($policy);

        // Get the compiled directives
        $directives = $policy->toArray();
        
        $this->comment('Current CSP Directives:');
        $this->line('');

        foreach ($directives as $directive => $sources) {
            $this->comment($directive);
            
            if (empty($sources)) {
                $this->line('  (no sources specified)');
            } else {
                foreach ($sources as $source) {
                    $this->line("  - {$source}");
                }
            }
            $this->line('');
        }

        $this->info('CSP Report URI: ' . (config('csp.report_uri') ?: 'Not configured'));
        
        // Show environment-specific information
        $this->showEnvironmentInfo();
        
        // Show potential security considerations
        $this->showSecurityConsiderations($directives);
    }

    private function showEnvironmentInfo()
    {
        $this->line('');
        $this->comment('Environment-Specific Configuration:');

        if (app()->environment(['local', 'testing'])) {
            $this->info('  ✓ Development mode - Localhost sources allowed');
            $this->info('  ✓ Permissive frame ancestors for development tools');
        }

        if (app()->environment('production')) {
            $this->info('  ✓ Production mode - Enhanced security enabled');
            $this->info('  ✓ Upgrade insecure requests enabled');
            $this->info('  ✓ Block all mixed content enabled');
            $this->info('  ✓ Frame ancestors set to none');
        }
    }

    private function showSecurityConsiderations(array $directives)
    {
        $this->line('');
        $this->comment('Security Considerations:');

        // Check for unsafe directives
        $scriptSources = $directives['script-src'] ?? [];
        $styleSources = $directives['style-src'] ?? [];
        
        if (in_array("'unsafe-inline'", $scriptSources)) {
            $this->warn('  ⚠️  Script unsafe-inline is enabled - required for Livewire/Filament');
        }

        if (in_array("'unsafe-eval'", $scriptSources)) {
            $this->warn('  ⚠️  Script unsafe-eval is enabled - required for Vite/Livewire');
        }

        if (in_array("'unsafe-inline'", $styleSources)) {
            $this->info('  ℹ️  Style unsafe-inline is enabled - required for Tailwind CSS');
        }

        // Check security features
        $frameAncestors = $directives['frame-ancestors'] ?? [];
        if (in_array("'none'", $frameAncestors)) {
            $this->info('  ✅ Frame ancestors set to none - good clickjacking protection');
        }

        $objectSrc = $directives['object-src'] ?? [];
        if (in_array("'none'", $objectSrc)) {
            $this->info('  ✅ Object-src set to none - good plugin security');
        }

        $this->line('');
        $this->comment('Tips:');
        $this->line('  • Monitor CSP violation reports if configured');
        $this->line('  • Test changes in report-only mode first');
        $this->line('  • Use browser developer tools to check for violations');
        $this->line('  • Consider using nonces for critical inline scripts');
    }
}