<?php

namespace App\Providers;

use App\Events\ImportedCSVProcessed;
use App\Listeners\DeployEntryProcessingJobs;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * Note: SendEmailVerificationNotification is automatically registered by Laravel
     * when Features::emailVerification() is enabled. Explicitly registering it here
     * would cause duplicate emails to be sent.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ImportedCSVProcessed::class => [
            DeployEntryProcessingJobs::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('regapp', \SocialiteProviders\NFDIAAI\Provider::class);
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Override to prevent duplicate email verification listener registration.
     * Laravel's base EventServiceProvider already registers this automatically,
     * but it seems to be registered twice. This override prevents the duplicate.
     */
    protected function configureEmailVerification(): void
    {
        // Intentionally left empty to prevent Laravel from auto-registering
        // SendEmailVerificationNotification, as it's already being registered elsewhere
    }
}
