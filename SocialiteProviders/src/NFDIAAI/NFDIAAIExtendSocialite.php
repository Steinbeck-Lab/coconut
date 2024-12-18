<?php

namespace SocialiteProviders\NFDIAAI;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NFDIAAIExtendSocialite
{
    /**
     * Register the provider.
     *
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('nfdi-aai', Provider::class);
    }
}
